document.querySelector('#scan').addEventListener('click', async () => {
  const status = document.querySelector('#status');
  const result = document.querySelector('#result');
  result.textContent = '';
  try {
    const [tab] = await browser.tabs.query({active:true,currentWindow:true});
    if (!tab?.url || new URL(tab.url).hostname !== 'www.carrefour.fr') throw new Error('Ouvre une page Carrefour.');
    const [response] = await browser.scripting.executeScript({
      target:{tabId:tab.id},
      func: () => {
        const rows = new Map();
        let marketplace = 0, ambiguous = 0;
        for (const article of document.querySelectorAll('article[data-testid]')) {
          const gtin = article.getAttribute('data-testid');
          if (!/^\d{13}$/.test(gtin)) continue;
          const text = article.innerText;
          if (/vendu\s+et\s+livré\s+par|plus d.offres à partir de/i.test(text)) { marketplace++; continue; }
          const amounts = [...text.matchAll(/(?<![\d.,])(\d+)\s*[,.]\s*(\d{2})\s*€(\s*\/\s*(KG|L)\b)?/gi)];
          const item = amounts.filter(m => !m[3]);
          const unit = amounts.filter(m => m[3]);
          if (item.length !== 1 || !/\bAcheter\b/i.test(text)) { ambiguous++; continue; }
          const cents = m => Number(m[1])*100 + Number(m[2]);
          const promo = text.match(/Ouvrir description de la promotion\s+([^\n]+)/i);
          rows.set(gtin, {gtin, amount_minor:cents(item[0]), currency:'EUR', price_basis:'item',
            unit_price:unit.length===1 ? cents(unit[0])/100 : null,
            unit_of_measure:unit.length===1 ? unit[0][4].toUpperCase() : null,
            promotion_label:promo?.[1].trim() || null, sponsored:/Sponsorisé/i.test(text)});
        }
        let store = null;
        for (const script of document.scripts) {
          const s = script.textContent || '';
          const id = s.match(/"user_storeID"\s*:\s*"(\d+)"/);
          const cart = s.match(/"user_storeIDM"\s*:\s*"(\d+)"/);
          const mode = s.match(/"cart_storeService"\s*:\s*"([^"]+)"/);
          if (id && cart && mode) { store = {store_id:id[1],cart_store_id:cart[1],mode:mode[1]}; break; }
        }
        return {store, marketplace_excluded:marketplace, ambiguous_excluded:ambiguous, products:[...rows.values()]};
      }
    });
    const data = response.result;
    status.textContent = data.products.length + ' produit(s) détecté(s), ' + data.marketplace_excluded + ' Marketplace et ' + data.ambiguous_excluded + ' ambigu(s) exclus.';
    result.textContent = JSON.stringify(data,null,2);
  } catch (error) { status.textContent = error.message; }
});