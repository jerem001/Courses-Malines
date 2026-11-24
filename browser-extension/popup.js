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
        const rows = [];
        for (const article of document.querySelectorAll('article[data-testid]')) {
          const gtin = article.getAttribute('data-testid');
          if (!/^\d{13}$/.test(gtin)) continue;
          const text = article.innerText;
          const amounts = [...text.matchAll(/\b(\d+)[,.](\d{2})\s*€/g)].map(m => m[0]);
          rows.push({gtin, text, displayedAmounts: amounts});
        }
        return rows;
      }
    });
    const rows = response.result || [];
    status.textContent = rows.length + ' fiche(s) détectée(s). Diagnostic local uniquement.';
    result.textContent = JSON.stringify(rows,null,2);
  } catch (error) { status.textContent = error.message; }
});