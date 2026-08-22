const CJK_RE = /[\u4e00-\u9fa5]/;
const LATIN_RE = /[a-zA-Z0-9]/;

function tokenize(text) {
  const tokens = [];
  let latin = '';
  let prevCjk = null;
  const flushLatin = () => {
    if (latin) {
      tokens.push(latin);
      latin = '';
    }
  };
  for (const ch of String(text)) {
    if (CJK_RE.test(ch)) {
      flushLatin();
      if (prevCjk) tokens.push(prevCjk + ch);
      prevCjk = ch;
    } else {
      prevCjk = null;
      if (LATIN_RE.test(ch)) {
        latin += ch.toLowerCase();
      } else {
        flushLatin();
      }
    }
  }
  flushLatin();
  return tokens;
}

function buildMatchQuery(keyword) {
  const tokens = tokenize(keyword).slice(0, 12);
  if (tokens.length === 0) return null;
  return tokens.map((t) => `"${t}"`).join(' AND ');
}

module.exports = { tokenize, buildMatchQuery };
