const { seedHash, seededInt } = require('../lib');

function publishOne({ channelKind, channelId, articleId }) {
  const hex = seedHash(['pub', String(channelKind), String(channelId), String(articleId)]);
  const failN = seededInt(hex, 1, 10);
  if (failN === 1) {
    return { status: 'failed', url: '', error: '目标站点暂时拒绝投稿' };
  }
  return {
    status: 'success',
    url: `https://pub.example/${channelKind}/${channelId}/${articleId}.html`,
    error: '',
  };
}

function inclusionResult(query, target) {
  const hex = seedHash(['inc', query, target || '']);
  const included = seededInt(hex, 0, 1);
  return {
    included,
    snippet: included ? `已发现与「${query}」匹配的收录结果` : `暂未发现「${query}」的稳定收录`,
    screenshot_path: `/api/placeholder/screenshot?q=${encodeURIComponent(query)}`,
  };
}

function keywordIndex(keyword) {
  const hex = seedHash(['idx', keyword]);
  const index = seededInt(hex, 80, 9800);
  const mobile = Math.floor(index * 0.62);
  const sem = (seededInt(hex.slice(8), 50, 1800) / 100).toFixed(2);
  const pc = Math.floor(index * 0.28);
  const tails = ['哪家好', '多少钱', '怎么做', '案例', '服务商', '培训', '工具', '平台', '公司', '报价']
    .map((s, i) => {
      const h = seedHash(['tail', keyword, s]);
      return {
        keyword: keyword + s,
        index: Math.max(10, index - i * 70 - seededInt(h, 0, 40)),
        mobile_index: Math.max(8, mobile - i * 40),
        sem_recommend_price_avg: (seededInt(h.slice(8), 30, 900) / 100).toFixed(2),
        bidword_pcpv: Math.max(5, pc - i * 12),
      };
    });
  tails.unshift({
    keyword,
    index,
    mobile_index: mobile,
    sem_recommend_price_avg: sem,
    bidword_pcpv: pc,
  });
  return tails;
}

module.exports = { publishOne, inclusionResult, keywordIndex };
