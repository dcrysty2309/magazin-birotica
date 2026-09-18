const fs = require('fs');
const htmlPath = 'D:/proiecte/notix/docs/decizii-marja-subcategorii.html';
let html = fs.readFileSync(htmlPath, 'utf8');

const data = JSON.parse(html.match(/const DATA = (\[.*?\]);/s)[1]);
const produse = JSON.parse(html.match(/const PRODUSE = (\{.*?\});/s)[1]);

function slug(topcat, sub) { return (topcat + '|' + sub).toLowerCase().replace(/\s+/g, '-'); }
function parseLei(s) { if (!s) return null; const n = parseFloat(String(s).replace(/[^0-9,.]/g, '').replace(',', '.')); return isNaN(n) ? null : n; }
function isJunk(s) { return !s.pret && /^https?:\/\/[^/]+\/?$/.test((s.link || '').trim()); }

const weak = data.filter(d => d.incredere === 'Slabă' || d.incredere === 'DE REVIZUIT');

const QUIZ = {};
let n = 0;
weak.forEach(d => {
  const key = slug(d.topcat, d.sub);
  const produseList = produse[key] || [];
  produseList.forEach(p => {
    if (p.quizRezolvat) return; // marja confirmata solida prin cercetare, nu mai necesita decizie manuala
    n++;
    const cost = parseLei(p.cost);
    const comparatii = p.surse
      .filter(s => !isJunk(s))
      .map(s => {
        const pret = parseLei(s.pret);
        const margin = (pret != null && cost != null) ? Math.round((pret - cost) / pret * 1000) / 10 : null;
        return {
          sursaNume: s.nume,
          sursaLink: s.link || '',
          pret: s.pret || null,
          match: s.match,
          margin,
        };
      });
    QUIZ['p' + n] = {
      topcat: d.topcat,
      sub: d.sub,
      notaSub: d.nota || '',
      produs: p.produs,
      produsLink: p.link,
      cost: p.cost,
      comparatii,
    };
  });
});

console.log('Total intrebari quiz (produse):', Object.keys(QUIZ).length, 'din', weak.length, 'subcategorii');

const quizStr = JSON.stringify(QUIZ);
html = html.replace(/const QUIZ = \{.*?\};/s, 'const QUIZ = ' + quizStr + ';');
fs.writeFileSync(htmlPath, html);
console.log('QUIZ rescris OK,', quizStr.length, 'bytes');
