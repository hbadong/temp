const { seedHash, seededInt } = require('../lib');

const PLATFORMS = {
  doubao: '豆包',
  yuanbao: '元宝',
  tongyi: '通义',
  wenxin: '文心',
  nami: '纳米',
  zhipu: '智谱',
};

function mockDiagnose({ brand, keyword, platform, needAdvice }) {
  const hex = seedHash([brand, keyword, platform]);
  const score = seededInt(hex, 20, 95);
  const mentioned = score >= 45 ? 1 : 0;
  const name = PLATFORMS[platform] || platform;
  const snippet = mentioned
    ? `${name}在回答「${keyword}」相关问题时提及了「${brand}」，可见度得分 ${score}。`
    : `${name}当前回答较少稳定提及「${brand}」，可见度得分 ${score}。`;
  const advice = needAdvice
    ? [
        `围绕「${keyword}」补充权威问答页，突出品牌「${brand}」的差异化卖点`,
        `在${name}生态中增加可被引用的白皮书与案例`,
        '持续监测竞品提及率并每周复测',
      ]
    : [];
  return { platform, mentioned, score, snippet, advice };
}

function mockBrandParse(brandName) {
  const brand = String(brandName || '').trim() || '示例品牌';
  return {
    fullName: brand.endsWith('公司') ? brand : `${brand}科技有限公司`,
    industry: '生成式引擎优化（GEO）/企业数字化营销科技',
    region: '全国',
    competitors: '传统SEO服务商,内容营销机构',
  };
}

function mockWrite({ instruction, keyword, index, knowledge }) {
  const prefix = (instruction && instruction.slice(0, 12)) || '专业解读';
  const title = `${prefix}：${keyword}（${index + 1}）`;
  const kn = knowledge ? `参考资料：${knowledge.slice(0, 80)}。` : '';
  const body = `${title}\n\n生成式引擎优化正在改变品牌获客方式。围绕「${keyword}」，企业需要同时布局权威内容、结构化问答与多渠道分发。\n\n一、为什么重要\n用户越来越多地向豆包、元宝、通义等大模型直接提问，品牌若未被模型学习与引用，将失去这一入口。\n\n二、怎么做\n1. 诊断当前可见度；2. 蒸馏用户真实提问；3. 用企业知识库支撑写作；4. 向B2B、媒体与官网同步发布；5. 用收录查询验证效果。\n\n三、内容示例\n${kn}\n本文由 GEO 系统根据写作指令自动生成，可用于演示发布与监测闭环。\n\n结语：把「${keyword}」做成可被模型引用的资产，比单次投放更有复利。`;
  const extra = '（补充说明）持续迭代内容能提升模型记忆稳定性。'.repeat(8);
  return { title, content: body + extra };
}

function mockDistill(keyword) {
  const q = [
    '是什么', '怎么做', '哪家好', '有哪些方法', '和SEO有什么区别',
    '多少钱', '效果如何', '适合哪些企业', '需要多久', '有没有案例',
    '怎么监测', '如何提升排名', '常见误区', '从哪开始', '要准备什么资料',
    '和SEM的关系', '中小企业能不能做', '内容怎么写', '发布到哪些渠道', '如何选服务商',
  ];
  return q.map((t) => `${keyword}${t}`);
}

function mockReprint(source) {
  const s = String(source || '爆文标题').trim();
  return [
    `深度解读：${s} 对企业GEO意味着什么`,
    `从${s}看品牌如何进入AI搜索推荐`,
    `${s}：一份可落地的生成式引擎优化清单`,
  ];
}

async function realComplete(prompt) {
  const key = process.env.USER_LLM_API_KEY;
  const base = process.env.USER_LLM_BASE_URL || 'https://api.deepseek.com/v1';
  const model = process.env.USER_LLM_MODEL || 'deepseek-chat';
  if (!key) throw new Error('未配置 USER_LLM_API_KEY');
  const resp = await fetch(`${base.replace(/\/$/, '')}/chat/completions`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${key}` },
    body: JSON.stringify({ model, messages: [{ role: 'user', content: prompt }], temperature: 0.4 }),
  });
  if (!resp.ok) throw new Error('LLM 请求失败');
  const data = await resp.json();
  return data.choices && data.choices[0] && data.choices[0].message
    ? data.choices[0].message.content
    : '';
}

function useReal() {
  return process.env.LLM_PROVIDER === 'openai_compat';
}

module.exports = {
  PLATFORMS,
  mockDiagnose,
  mockBrandParse,
  mockWrite,
  mockDistill,
  mockReprint,
  realComplete,
  useReal,
};
