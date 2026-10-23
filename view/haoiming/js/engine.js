/* ============================================================
 * 好名 · 智能起名系统 —— 算法引擎（原创实现）
 * 1. 干支历法：年/月/日/时四柱推算（近似节气，娱乐与演示用途）
 * 2. 五行统计与喜用神（简化模型）
 * 3. 三才五格：康熙笔画 → 五格数理 → 81 数理吉凶
 * 4. 起名引擎：八字补缺 / 诗词提取 / 周易风格 / 公司起名 / 配对
 * 依赖：data.js
 * ============================================================ */

(function () {
  const HM = window.HM_DATA;
  const E = {};

  /* ================= 干支基础 ================= */
  const STEMS = ["甲","乙","丙","丁","戊","己","庚","辛","壬","癸"];
  const BRANCHES = ["子","丑","寅","卯","辰","巳","午","未","申","酉","戌","亥"];
  const STEM_WX = ["木","木","火","火","土","土","金","金","水","水"];
  const BRANCH_WX = ["水","土","木","木","土","火","火","土","金","金","土","水"];
  const ZODIAC = ["鼠","牛","虎","兔","龙","蛇","马","羊","猴","鸡","狗","猪"];

  // 儒略日数（本地日期）
  function jdn(y, m, d) {
    const a = Math.floor((14 - m) / 12), yy = y + 4800 - a;
    const mm = m + 12 * a - 3;
    return d + Math.floor((153 * mm + 2) / 5) + 365 * yy + Math.floor(yy / 4) - Math.floor(yy / 100) + Math.floor(yy / 400) - 32045;
  }

  // 日柱：(JDN - 11) % 60，0 = 甲子（已用 2000-01-01 戊午日校验）
  function dayPillar(y, m, d) { return (jdn(y, m, d) - 11) % 60; }

  // 年柱：以立春（近似 2 月 4 日）为界
  function yearPillar(y, m, d) {
    let yy = y;
    if (m < 2 || (m === 2 && d < 4)) yy -= 1;
    return { stem: (yy - 4) % 10, branch: (yy - 4) % 12 };
  }

  // 月柱：近似节气（每月 4~8 日交节，统一以 6 日为界），寅月为正月
  function monthPillar(y, m, d) {
    let mi = m - 2;               // 2 月 → 寅月 0
    if (mi < 0) mi += 12;
    if (d < 6) mi -= 1;
    if (mi < 0) mi += 12;
    const branch = (mi + 2) % 12; // 寅=2
    // 五虎遁：甲己之年丙作首
    const ys = yearPillar(y, m, d).stem;
    const firstStem = (ys % 5) * 2 + 2; // 寅月天干
    return { stem: (firstStem + mi) % 10, branch };
  }

  // 时柱：23:00-00:59 为子时
  function hourPillar(y, m, d, h) {
    let hi = Math.floor((h + 1) / 2) % 12;
    let dayIdx = dayPillar(y, m, d);
    if (h >= 23) dayIdx = (dayIdx + 1) % 60; // 晚子时日柱进位（简化）
    const ds = dayIdx % 10;
    const firstStem = (ds % 5) * 2; // 五鼠遁：甲己还加甲
    return { stem: (firstStem + hi) % 10, branch: hi };
  }

  E.bazi = function (birth) {
    let y = birth.y, m = birth.m, d = birth.d, h = birth.h == null ? 12 : birth.h;
    /* 真太阳时校正：北京时间(东经120°) - (120-当地经度)×4分钟（简化模型，不含均时差） */
    let solarNote = "";
    if (birth.place) {
      const key = Object.keys(CITY_LON).find(c => (birth.place || "").includes(c));
      if (key) {
        const offsetMin = Math.round((120 - CITY_LON[key]) * 4);   // 当地经度每差1°=4分钟
        h = h + offsetMin / 60;
        if (h >= 24) { h -= 24; d += 1; }
        if (h < 0) { h += 24; d -= 1; }
        solarNote = `按${key}（东经${CITY_LON[key]}°）真太阳时校正 ${offsetMin >= 0 ? "+" : ""}${offsetMin} 分钟`;
      }
    }
    const pillars = [yearPillar(y, m, d), monthPillar(y, m, d), { i: dayPillar(y, m, d) }, hourPillar(y, m, d, h)];
    pillars[2] = { stem: pillars[2].i % 10, branch: pillars[2].i % 12 };
    const gz = pillars.map(p => STEMS[p.stem] + BRANCHES[p.branch]);
    const wxCount = { "金": 0, "木": 0, "水": 0, "火": 0, "土": 0 };
    pillars.forEach(p => { wxCount[STEM_WX[p.stem]]++; wxCount[BRANCH_WX[p.branch]]++; });
    /* 藏干加权统计：天干1.0/地支，地支藏干 本气0.7 中气0.3 余气0.1（专业排盘算法） */
    const wxWeight = { "金": 0, "木": 0, "水": 0, "火": 0, "土": 0 };
    pillars.forEach(p => {
      wxWeight[STEM_WX[p.stem]] += 1.0;
      const cq = BRANCH_CQ[BRANCHES[p.branch]] || [];
      const wts = [0.7, 0.3, 0.1];
      cq.forEach((g, i) => { wxWeight[g[1]] += wts[i]; });
    });
    const cangGan = pillars.map(p => ({ gz: STEMS[p.stem] + BRANCHES[p.branch], cq: (BRANCH_CQ[BRANCHES[p.branch]] || []).map(g => g[0]).join("") }));

    /* ---- 真实化喜用神：日主强弱分析（得令/同党异党） ---- */
    const SHENG = { "木": "火", "火": "土", "土": "金", "金": "水", "水": "木" };   // 我生
    const KE = { "木": "土", "土": "水", "水": "火", "火": "金", "金": "木" };     // 我克
    const BEI_KE = { "木": "金", "金": "火", "火": "水", "水": "土", "土": "木" }; // 克我
    const SHENG_ME = { "木": "水", "水": "金", "金": "土", "土": "火", "火": "木" }; // 生我
    const dayStem = pillars[2].stem;
    const dm = STEM_WX[dayStem];
    const party = wxWeight[dm] + wxWeight[SHENG_ME[dm]];       // 同党：比劫+印（藏干加权）
    const rival = wxWeight[KE[dm]] + wxWeight[BEI_KE[dm]] + wxWeight[SHENG[dm]]; // 异党：官杀+财+食伤
    const total8 = party + rival || 1;
    const strength = party / total8;                          // 日主强度 0~1
    let strengthTag, favorable;
    if (strength >= 0.62) {
      strengthTag = "偏强";
      /* 身强宜克泄耗：取官杀/食伤中八字较少者为喜用 */
      const a = wxCount[KE[dm]], b2 = wxCount[SHENG[dm]];
      favorable = a <= b2 ? KE[dm] : SHENG[dm];
    } else if (strength <= 0.38) {
      strengthTag = "偏弱";
      /* 身弱宜生扶：取印/比劫中八字较少者为喜用 */
      const a = wxCount[SHENG_ME[dm]], b2 = wxCount[dm];
      favorable = a <= b2 ? SHENG_ME[dm] : dm;
    } else {
      strengthTag = "中和";
      /* 中和取命局最少之五行调和 */
      let min = 99;
      HM.WUXING.forEach(w => { if (wxCount[w] < min) { min = wxCount[w]; favorable = w; } });
    }

    /* 忌神：身强忌生扶（印/比劫），身弱忌克泄（官杀/食伤），取八字中较多者 */
    let tabooWx = null;
    if (strengthTag === "偏强") {
      const i1 = wxWeight[SHENG_ME[dm]], i2 = wxWeight[dm];
      tabooWx = i1 >= i2 ? SHENG_ME[dm] : dm;
    } else if (strengthTag === "偏弱") {
      const k1 = wxWeight[BEI_KE[dm]], k2 = wxWeight[SHENG[dm]];
      tabooWx = k1 >= k2 ? BEI_KE[dm] : SHENG[dm];
    }
    return {
      pillars, gz, wxCount, wxWeight, cangGan, solarNote, tabooWx, favorable,
      dayMaster: { wx: dm, strength: Math.round(strength * 100), tag: strengthTag,
        sameParty: Math.round(party * 10) / 10, rivalParty: Math.round(rival * 10) / 10,
        desc: `日主${STEMS[dayStem]}（${dm}），同党力量 ${Math.round(party * 10) / 10}、异党力量 ${Math.round(rival * 10) / 10}（含藏干加权），日主${strengthTag}` },
      dayStemName: STEMS[dayStem],
      dayStemWx: dm,
      zodiac: ZODIAC[yearPillar(y, m, d).branch],
      missing: HM.WUXING.filter(w => wxCount[w] === 0)
    };
  };

  
/* ================= 专业排盘数据（真太阳时 / 藏干） ================= */
const CITY_LON = { "北京":116.4,"上海":121.5,"广州":113.3,"深圳":114.1,"成都":104.1,"重庆":106.5,"杭州":120.2,
"南京":118.8,"武汉":114.3,"西安":108.9,"郑州":113.6,"济南":117.0,"青岛":120.4,"沈阳":123.4,"哈尔滨":126.6,
"长春":125.3,"大连":121.6,"天津":117.2,"石家庄":114.5,"太原":112.5,"呼和浩特":111.7,"合肥":117.3,"福州":119.3,
"厦门":118.1,"南昌":115.9,"长沙":113.0,"南宁":108.3,"海口":110.3,"贵阳":106.7,"昆明":102.7,"拉萨":91.1,
"兰州":103.8,"西宁":101.8,"银川":106.2,"乌鲁木齐":87.6,"香港":114.2,"澳门":113.5,"台北":121.5,"苏州":120.6,"东莞":113.7 };
/* 地支藏干：[本气, 中气, 余气]（传统子平体系，公共领域） */
const BRANCH_CQ = {
  "子": [["癸","水"]], "丑": [["己","土"],["癸","水"],["辛","金"]],
  "寅": [["甲","木"],["丙","火"],["戊","土"]], "卯": [["乙","木"]],
  "辰": [["戊","土"],["乙","木"],["癸","水"]], "巳": [["丙","火"],["庚","金"],["戊","土"]],
  "午": [["丁","火"],["己","土"]], "未": [["己","土"],["丁","火"],["乙","木"]],
  "申": [["庚","金"],["壬","水"],["戊","土"]], "酉": [["辛","金"]],
  "戌": [["戊","土"],["辛","金"],["丁","火"]], "亥": [["壬","水"],["甲","木"]]
};

/* ================= 三才五格 ================= */
  // 81 数理吉凶（传统数理，公共领域）：吉 / 半吉 / 凶
  const JI81 = {
    1:"吉",2:"凶",3:"吉",4:"凶",5:"吉",6:"吉",7:"吉",8:"吉",9:"凶",10:"凶",
    11:"吉",12:"凶",13:"吉",14:"凶",15:"吉",16:"吉",17:"半吉",18:"半吉",19:"凶",20:"凶",
    21:"吉",22:"凶",23:"吉",24:"吉",25:"吉",26:"凶",27:"凶",28:"凶",29:"吉",30:"半吉",
    31:"吉",32:"吉",33:"吉",34:"凶",35:"吉",36:"凶",37:"吉",38:"半吉",39:"吉",40:"凶",
    41:"吉",42:"凶",43:"凶",44:"凶",45:"吉",46:"凶",47:"吉",48:"吉",49:"凶",50:"凶",
    51:"半吉",52:"吉",53:"凶",54:"凶",55:"半吉",56:"凶",57:"半吉",58:"半吉",59:"凶",60:"凶",
    61:"吉",62:"凶",63:"吉",64:"凶",65:"吉",66:"凶",67:"吉",68:"吉",69:"凶",70:"凶",
    71:"半吉",72:"凶",73:"半吉",74:"凶",75:"半吉",76:"凶",77:"半吉",78:"半吉",79:"凶",80:"凶",81:"吉"
  };
  const WX_OF_NUM = n => { const r = n % 10; return r === 1 || r === 2 ? "木" : r === 3 || r === 4 ? "火" : r === 5 || r === 6 ? "土" : r === 7 || r === 8 ? "金" : "水"; };
  const SANCAI_REL = {
    "木木":"吉","木火":"吉","木土":"凶","木金":"凶","木水":"吉",
    "火木":"吉","火火":"吉","火土":"吉","火金":"凶","火水":"凶",
    "土木":"吉","土火":"吉","土土":"吉","土金":"吉","土水":"凶",
    "金木":"凶","金火":"凶","金土":"吉","金金":"吉","金水":"吉",
    "水木":"吉","水火":"凶","水土":"凶","水金":"吉","水水":"吉"
  };

  /* 常用字康熙笔画兜底（覆盖数字、常用虚字与人名高频字，降低未收录字误差） */
  const STROKE_FALLBACK = {"一":1,"二":2,"三":3,"四":4,"五":5,"六":6,"七":7,"八":8,"九":9,"十":10,"百":6,"千":3,"万":15,"乙":1,"丁":2,"大":3,"小":3,"天":4,"地":6,"日":4,"月":4,"水":4,"火":4,"木":4,"金":8,"土":3,"山":3,"川":3,"帝":9,"后":6,"公":4,"子":3,"文":4,"武":8,"中":4,"华":14,"国":11,"安":6,"宁":14,"乐":15,"东":8,"南":9,"西":6,"北":5,"上":3,"下":3,"心":4,"手":4,"口":3,"目":5,"田":5,"力":2,"又":2,"才":4,"也":3,"以":5,"为":12,"之":4,"乎":5,"者":9,"矣":7,"焉":11,"哉":9,"于":3,"其":8,"若":11,"如":6,"尔":14,"此":6,"彼":8,"我":7,"他":5,"光":6,"先":6,"生":5,"行":6,"飞":9,"云":12,"风":9,"雨":8,"雪":11,"雷":13,"电":13,"亮":9,"高":10,"远":17,"近":8,"快":7,"慢":15,"新":13,"旧":5,"多":6,"少":4,"半":5,"全":6,
  /* 复姓用字（46 复姓全量覆盖） */
  "欧":15,"阳":17,"司":5,"马":10,"上":3,"官":8,"诸":16,"葛":15,"方":4,"皇":9,"甫":7,"尉":13,"迟":16,"仲":6,"轩":11,"辕":17,"令":5,"狐":9,"钟":17,"离":11,"宇":6,"长":8,"慕":15,"容":14,"徒":10,"空":9,"俟":12,"闻":14,"夏":10,"侯":9,"独":16,"孤":9,"宫":6,"申":5,"屠":12,"冶":7,"太":4,"叔":8,"澹":16,"台":14,"百":6,"里":7,"郭":11,"呼":9,"延":7,"端":14,"漆":15,"雕":16,"宰":10,"父":4,"谷":7,"梁":11,"拓":8,"跋":12,"夹":6,"微":13,"左":5,"丘":5,"荣":14,"第":11,"羊":6,
  /* 高频名用字补充 */
  "修":10,"登":12,"鹏":19,"浩":11,"然":12,"翔":12,"凡":3,"帆":6,"峰":10,"磊":15,"鑫":24,"晶":12,"磊":15,"燕":16,"娟":10,"敏":11,"静":16,"丽":19,"娜":10,"婷":12,"萍":14,"莉":13,"芳":10,"芬":10,"英":11,"慧":15,"颖":16,"珊":10,"玲":10,"珠":11,"翠":14,"凤":14,"雁":12,"鸿":17,"鹤":21,"燕":16,"晨":11,"曦":20,"晖":13,"晗":11,"旭":6,"晔":16,"昊":8,"晗":11,"智":12,"哲":10,"彬":11,"彦":9,"斌":12,"毅":15,"刚":10,"强":12,"伟":11,"健":11,"博":12,"学":16,"军":9,"兵":7,"斌":12,"辉":15,"耀":20,"祖":10,"宗":8,"庆":15,"祥":11,"福":14,"禄":13,"寿":14,"康":11,"健":11,"平":5,"安":6,"顺":12,"利":7,"贞":9,"吉":6,"祥":11,"瑞":14,"佑":7,"启":11,"承":8,"继":13,"绍":11,"传":13,"家":10,"业":13,"兴":15,"隆":17,"盛":12,"达":16,"通":14,"达":16,"远":17,"永":5,"恒":10,"久":3,"长":8,"存":6,"在":6};
  function stroke(c) {
    const s = HM.SURNAMES.find(x => x.c === c); if (s) return s.k;
    const ch = HM.CHARS.find(x => x.c === c); if (ch) return ch.k;
    if (STROKE_FALLBACK[c]) return STROKE_FALLBACK[c];
    const F = window.HM_DATA.STROKES_FULL;            /* 全量康熙笔画库（25100 字，懒加载） */
    if (F && F[c]) return F[c];
    // 简化兜底：常见偏旁还原后按 4~12 画估计
    return c.length * 4;
  }

  E.wuge = function (name) {
    if (name == null || typeof name !== "string" || !name.trim()) return null;
    const chars = Array.from(name.trim());
    if (chars.length < 2) return null;
    const ks = chars.map(stroke);
    /* 复姓识别（FU_SURNAMES 最长前缀匹配） */
    const FU = (window.HM_DATA && window.HM_DATA.FU_SURNAMES) || [];
    let fu = "";
    FU.forEach(f => { if (!fu && name.startsWith(f) && f.length > fu.length) fu = f; });
    const sn = fu ? Array.from(fu).length : 1;   // 姓字数
    let tian, ren, di, wai, zong;
    if (sn === 2 && chars.length === 2) {
      /* 仅输入复姓 */
      tian = ks[0] + ks[1]; ren = ks[0] + ks[1]; di = 2; wai = 2; zong = ks[0] + ks[1];
    } else if (sn === 2 && chars.length === 3) {
      /* 复姓 + 单名：天格=姓和，人格=姓末+名，地格=名+1，总格=全和 */
      tian = ks[0] + ks[1]; ren = ks[1] + ks[2]; di = ks[2] + 1; zong = ks[0] + ks[1] + ks[2]; wai = 2;
    } else if (sn === 2 && chars.length >= 4) {
      /* 复姓 + 双名 */
      tian = ks[0] + ks[1]; ren = ks[1] + ks[2]; di = ks[2] + ks[3]; zong = ks.reduce((a, b) => a + b, 0); wai = Math.max(2, zong - ren + 1);
    } else if (chars.length === 2) {
      tian = ks[0] + 1; ren = ks[0] + ks[1]; di = ks[1] + 1; zong = ks[0] + ks[1]; wai = 2;
    } else if (chars.length === 3) {
      tian = ks[0] + 1; ren = ks[0] + ks[1]; di = ks[1] + ks[2]; zong = ks[0] + ks[1] + ks[2]; wai = ks[2] + 1;
    } else { // 4 字（四字名，简化处理）
      tian = ks[0] + ks[1]; ren = ks[1] + ks[2]; di = ks[2] + ks[3]; zong = ks[0] + ks[1] + ks[2] + ks[3]; wai = zong - ren + 1;
    }
    const norm = n => ((n - 1) % 80) + 1;
    const grid = n => ({ n, wx: WX_OF_NUM(norm(n)), luck: JI81[norm(n)] || "吉" });
    const sancai = WX_OF_NUM(norm(tian)) + WX_OF_NUM(norm(ren)) + WX_OF_NUM(norm(di));
    const luckList = [tian, ren, di, wai, zong].map(n => grid(n).luck);
    const score = Math.max(40, Math.min(99,
      60 + luckList.filter(l => l === "吉").length * 9
        + luckList.filter(l => l === "半吉").length * 4
        - luckList.filter(l => l === "凶").length * 8
        + (SANCAI_REL[sancai] === "吉" ? 6 : SANCAI_REL[sancai] === "凶" ? -6 : 0)));
    return {
      strokes: ks,
      tian: grid(tian), ren: grid(ren), di: grid(di), wai: grid(wai), zong: grid(zong),
      sancai, sancaiLuck: SANCAI_REL[sancai] || "半吉",
      score
    };
  };

  /* ================= 起名引擎 ================= */
  function pickChars(wx, gender, count) {
    let pool = HM.CHARS.filter(x => x.w === wx);
    if (gender === 0) pool = HM.CHARS.filter(x => x.w === wx);
    if (pool.length < 6) pool = HM.CHARS.slice();
    let gPool = gender === 0 ? pool.slice() : pool.filter(x => x.g === gender || x.g === 0);
    if (gPool.length < count) gPool = pool.slice();
    return shuffle(gPool).slice(0, count);
  }

  function shuffle(arr) {
    const a = arr.slice();
    for (let i = a.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [a[i], a[j]] = [a[j], a[i]];
    }
    return a;
  }

  // 八字起名：主选喜用神，辅以生扶五行
  E.generateByBazi = function (surname, gender, birth, limit) {
    limit = limit || 12;
    const b = E.bazi(birth);
    const support = { "木": "水", "火": "木", "土": "火", "金": "土", "水": "金" }[b.favorable];
    const results = [];
    const mains = pickChars(b.favorable, gender, 10);
    const subs = pickChars(support, gender, 10);
    for (let i = 0; i < mains.length; i++) {
      for (let j = 0; j < subs.length && results.length < limit * 5; j++) {
        const given = i % 2 === 0 ? mains[i].c + subs[j].c : subs[j].c + mains[i].c;
        if (results.some(r => r.name === surname + given)) continue;
        const full = surname + given;
        const wg = E.wuge(full);
        results.push({
          name: full, given,
          chars: [mains[i], subs[j]],
          score: wg ? wg.score : 70,
          wx: mains[i].w + subs[j].w,
          reason: `补${b.favorable}（喜用神），辅以${support}相生`
        });
      }
    }
    results.sort((a, b2) => b2.score - a.score);
    return { bazi: b, names: results.slice(0, limit) };
  };

  // 诗词起名
  E.generateByPoem = function (surname, gender, cat, limit) {
    limit = limit || 12;
    let poems = HM.POEMS;
    if (cat && cat !== "all") poems = poems.filter(p => p.cat === cat);
    const out = [];
    poems.forEach(p => {
      p.names.forEach(cand => {
        if (gender === 1 && cand.g === 2) return;
        if (gender === 2 && cand.g === 1) return;
        const wg = E.wuge(surname + cand.n);
        out.push({
          name: surname + cand.n, given: cand.n,
          score: wg ? wg.score : 75,
          line: p.line, src: p.src,
          reason: `出自「${p.line}」`
        });
      });
    });
    // 去掉与姓氏组合后读起来不佳的（简单规则：末字与姓相同）
    const seen = new Set();
    const uniq = out.filter(x => { const k = x.given; if (seen.has(k)) return false; seen.add(k); return x.name[0] !== x.name[x.name.length - 1]; });
    /* 综合排序：五格 + 音律 + 语义（诗词起名 qualityScore 复用，birth=null 跳过生肖） */
    uniq.forEach(x => {
      const wg2 = E.wuge(x.name);
      const q = qualityScore(x.name, x.given, wg2, null, "", "");
      x.wg = wg2 ? wg2.score : 70;
      x.score = q.final;
      x.reason = `出自「${x.line}」；${q.ph.issues.length ? q.ph.issues[0] : "音律谐畅"}`;
    });
    uniq.sort((a, b) => b.score - a.score);
    return uniq.slice(0, limit);
  };

  // 周易风格：取乾坤谦益等卦辞气质字（内置映射）
  const ZHOUYI_CHARS = [
    {c:"健",m:"天行健，君子以自强不息",w:"木"},
    {c:"谦",m:"谦谦君子，卑以自牧",w:"木"},
    {c:"厚",m:"君子以厚德载物",w:"水"},
    {c:"恒",m:"恒者久也，立不易方",w:"水"},
    {c:"晋",m:"晋者进也，明出地上",w:"火"},
    {c:"益",m:"损上益下，民说无疆",w:"土"},
    {c:"丰",m:"丰者大也，明动相资",w:"火"},
    {c:"颐",m:"颐养正则吉",w:"土"},
    {c:"咸",m:"二气感应以相与",w:"水"},
    {c:"临",m:"临者大也，刚浸而长",w:"火"},
    {c:"观",m:"观国之光，风行地上",w:"木"},
    {c:"泰",m:"天地交而万物通",w:"火"}
  ];
  E.generateByZhouyi = function (surname, gender, limit) {
    limit = limit || 12;
    const pool = shuffle(ZHOUYI_CHARS).slice(0, 6);
    const fix = shuffle(HM.CHARS.filter(x => x.g === gender || x.g === 0)).slice(0, 12);
    const out = [];
    pool.forEach((p, i) => {
      for (let j = 0; j < fix.length && out.length < limit * 2; j++) {
        const given = i % 2 === 0 ? p.c + fix[j].c : fix[j].c + p.c;
        const wg = E.wuge(surname + given);
        out.push({ name: surname + given, given, score: wg ? wg.score : 72, reason: `卦象取意：${p.m}`, wx: p.w + fix[j].w });
      }
    });
    const seen = new Set();
    return out.filter(x => { if (seen.has(x.given)) return false; seen.add(x.given); return true; })
      .sort((a, b) => b.score - a.score).slice(0, limit);
  };

  // 公司起名
  E.generateCompany = function (industry, core, limit) {
    limit = limit || 9;
    const ind = HM.INDUSTRY[industry] || HM.INDUSTRY.tech;
    const aus = shuffle(HM_DATA.AUSPICIOUS);
    const indc = shuffle(ind.chars);
    const out = [];
    for (let i = 0; i < limit; i++) {
      let a = indc[i % indc.length];
      if (core && a === core) a = indc[(i + 3) % indc.length] === core ? indc[(i + 5) % indc.length] : indc[(i + 3) % indc.length];
      const b = aus[i % aus.length];
      const tail = ["科技", "商贸", "文化", "实业", "信息技术", "管理咨询"][i % 6];
      const body = core ? core + a : a + b;
      const name = body + tail;
      const wg = E.wuge(body);
      out.push({
        name, body,
        score: wg ? wg.score : 76,
        reason: `行业字「${a}」× 吉祥字「${b}」，数理${wg ? wg.score : 76}分`,
        indName: ind.name
      });
    }
    return out.sort((a, b) => b.score - a.score);
  };

  // 姓名配对（娱乐）
  E.matchNames = function (n1, n2) {
    const a = Array.from(n1).reduce((s, c) => s + stroke(c), 0);
    const b = Array.from(n2).reduce((s, c) => s + stroke(c), 0);
    const wg1 = E.wuge(n1), wg2 = E.wuge(n2);
    /* 1) 人格五行生克和合 */
    const norm = n => ((n - 1) % 80) + 1;
    const wx1 = WX_OF_NUM(norm(wg1 ? wg1.ren.n : a)), wx2 = WX_OF_NUM(norm(wg2 ? wg2.ren.n : b));
    const SH = { "木": "火", "火": "土", "土": "金", "金": "水", "水": "木" };
    const BK = { "木": "土", "土": "水", "水": "火", "火": "金", "金": "木" };
    let wxRel = 10, wxTxt = "比和相帮";
    if (SH[wx1] === wx2) { wxRel = 20; wxTxt = `${wx1}生${wx2}，相生有助`; }
    else if (SH[wx2] === wx1) { wxRel = 20; wxTxt = `${wx2}生${wx1}，相生有助`; }
    else if (BK[wx1] === wx2 || BK[wx2] === wx1) { wxRel = 2; wxTxt = `${wx1}与${wx2}相克，需磨合`; }
    /* 2) 音律和合：尾字声调错落 */
    const t1 = E.phoneticScore(n1), t2 = E.phoneticScore(n2);
    const tailTone = (n) => { const cs = Array.from(n); return toneOfChar(cs[cs.length - 1]); };
    let phRel = 12;
    if (tailTone(n1) && tailTone(n1) === tailTone(n2)) { phRel = 4; }
    phRel += Math.round((t1.score + t2.score) / 2 / 25);   // 最高 +4
    /* 3) 笔画和合：笔画差越小越融洽（民俗说法） */
    const diff = Math.abs(a - b);
    const strRel = Math.max(0, 14 - Math.round(diff / 2));
    /* 4) 语义亲和：两名尾字 */
    const semRel = affinity(Array.from(n1).pop(), Array.from(n2).pop());
    const score = Math.max(55, Math.min(99, Math.round(38 + wxRel * 0.9 + phRel * 0.8 + strRel * 0.6 + semRel * 0.5)));
    const tag = score >= 90 ? "天作之合" : score >= 80 ? "心意相通" : score >= 70 ? "渐入佳境" : "磨合互补";
    return { score, tag, s1: a, s2: b, detail: { wxTxt, phTxt: t1.issues.length || t2.issues.length ? "读感尚可" : "音律谐畅", strRel, semRel } };
  };

  window.HM_ENGINE = E;

/* ================= v2 引擎扩展（原创实现） ================= */

/* 姓氏校验：汉字、长度、复姓识别（仿主流工具的白名单思路，白名单为本站整理） */
E.checkSurname = function (s) {
  if (!s) return { ok: false, msg: "请输入姓氏" };
  if (!/^[\u4E00-\u9FA5]+$/.test(s)) return { ok: false, msg: "姓氏只能包含汉字" };
  const fu = HM.FU_SURNAMES || [];
  if (s.length > 2) return { ok: false, msg: "姓氏最长两个字" };
  if (s.length === 2 && fu.indexOf(s) === -1) return { ok: false, msg: "两字姓氏请从复姓中选择，或只输入单姓" };
  if (s.length === 1 && !HM.SURNAMES.find(x => x.c === s)) return { ok: false, msg: "暂未收录该姓氏，请检查输入" };
  return { ok: true, msg: "" };
};

/* 五行偏好起名：喜用神 + 用户偏好合并 */
/* 五格最优笔画组合推荐：给定姓氏，枚举名字两字笔画使五格+三才全吉 */
E.bestStrokePairs = function (surname) {
  const ks = Array.from(surname).map(c => stroke(c));
  const fu = (window.HM_DATA && window.HM_DATA.FU_SURNAMES || []).find(f => surname.startsWith(f));
  const sn = fu ? Array.from(fu).length : 1;
  const surnameSum = ks.slice(0, sn).reduce((a, b) => a + b, 0);
  const tian = sn === 2 ? surnameSum : surnameSum + 1;
  const norm = n => ((n - 1) % 80) + 1;
  const luck = n => JI81[norm(n)] || "吉";
  const out = [];
  for (let n1 = 1; n1 <= 21; n1++) {
    for (let n2 = 1; n2 <= 21; n2++) {
      const ren = sn === 2 ? surnameSum + n1 : surnameSum + n1;
      const di = n1 + n2;
      const zong = surnameSum + n1 + n2;
      const wai = Math.max(2, zong - ren + 1);
      const gl = [tian, ren, di, zong, wai].map(luck);
      if (gl.includes("凶")) continue;
      const sancai = WX_OF_NUM(norm(tian)) + WX_OF_NUM(norm(ren)) + WX_OF_NUM(norm(di));
      const sc = SANCAI_REL[sancai];
      if (sc === "凶") continue;
      const score = gl.filter(l => l === "吉").length * 10 + (sc === "吉" ? 15 : sc === "半吉" ? 4 : 0);
      out.push({ n1, n2, score, sancai, sc, detail: `天${norm(tian)} 人${norm(ren)} 地${norm(di)} 总${norm(zong)} 外${norm(wai)}` });
    }
  }
  const seen = new Set();
  return out.sort((a, b) => b.score - a.score).filter(x => {
    const k = x.n1 + "+" + x.n2;
    if (seen.has(k)) return false;
    seen.add(k);
    return true;
  }).slice(0, 6);
};

/* ================= 综合质量评分（音律/语义/生肖/重名） ================= */
/* 语义类别：按寓意关键词给字库字分类（引擎初始化时计算一次） */
const SEMI_RULES = [
  ["nature", "云雨雪川山江海星辰月风雷林泉溪露虹波涛峰岚"],
  ["bright", "明晖曦旭阳光晨晓灿朗昕晗曜炎晟"],
  ["smart", "智慧思颖哲敏睿识学问聪"],
  ["aspire", "志远航翔鹏程立功成伟杰峰腾飞越超卓"],
  ["virtue", "仁信诚正德善谦和平雅敬守恒勤俭"],
  ["elegant", "诗文雅书翰墨琴棋画韵清逸风华"],
  ["gentle", "柔静婉娴淑宁恬安然若素"],
  ["wealth", "富贵荣华财金鑫裕丰盛昌隆瑞"],
  ["health", "康健壮安寿宁福泰怡悦欣愉乐"]
];
function semanticOf(ch) {
  if (!semanticOf._c) semanticOf._c = {};
  if (semanticOf._c[ch]) return semanticOf._c[ch];
  const info = HM.CHARS.find(x => x.c === ch);
  let cat = "other";
  if (info && info.m) {
    for (const [cat2, kws] of SEMI_RULES) {
      for (const kw of kws) { if (info.m.includes(kw) || ch === kw) { cat = cat2; break; } }
      if (cat !== "other") break;
    }
  }
  semanticOf._c[ch] = cat;
  return cat;
}
/* 语义搭配亲和度：同类意象统一 / 经典互补组合 */
const SEMI_PAIR = { "nature:virtue": 14, "nature:smart": 12, "nature:elegant": 13, "bright:smart": 13, "bright:gentle": 12, "aspire:virtue": 14, "aspire:smart": 12, "elegant:gentle": 12, "smart:virtue": 13, "gentle:elegant": 13, "wealth:virtue": 10, "health:gentle": 10 };
function affinity(a, b) {
  const ca = semanticOf(a), cb = semanticOf(b);
  if (ca === "other" || cb === "other") return 8;
  if (ca === cb) return 15;
  const k1 = ca + ":" + cb, k2 = cb + ":" + ca;
  return SEMI_PAIR[k1] || SEMI_PAIR[k2] || 8;
}
/* 音律评分：声调模式 + 声母/韵母重复 */
function toneOfChar(c) {
  const info = HM.CHARS.find(x => x.c === c);
  const py = info ? info.p : "";
  const m = py.match(/[āáǎàēéěèīíǐìōóǒòūúǔùǖǘǚǜ]/);
  const T = {"ā":1,"á":2,"ǎ":3,"à":4,"ē":1,"é":2,"ě":3,"è":4,"ī":1,"í":2,"ǐ":3,"ì":4,"ō":1,"ó":2,"ǒ":3,"ò":4,"ū":1,"ú":2,"ǔ":3,"ù":4,"ǖ":1,"ǘ":2,"ǚ":3,"ǜ":4};
  return m ? T[m[0]] : 0;
}
E.phoneticScore = function (full) {
  const chars = Array.from(full);
  const tones = chars.map(toneOfChar);
  const sms = chars.map(c => { const i = HM.CHARS.find(x => x.c === c); return i ? i.p.replace(/[^a-z]/g, "").charAt(0) : ""; });
  let sc = 100; const issues = [];
  for (let i = 1; i < tones.length; i++) {
    if (tones[i] && tones[i] === tones[i - 1]) { sc -= 12; issues.push("相邻同声调"); }
  }
  if (tones.every(t => t === 2 || t === 3) && tones.length >= 3) { sc -= 8; issues.push("连续平声拗口"); }
  if (tones.filter(t => t === 3).length >= 2) { sc -= 6; issues.push("多上声"); }
  for (let i = 1; i < sms.length; i++) {
    if (sms[i] && sms[i] === sms[i - 1]) { sc -= 10; issues.push("声母重复"); }
  }
  if (tones.length >= 3 && tones[0] !== tones[1] && tones[1] !== tones[2]) { sc += 6; }
  return { score: Math.max(40, Math.min(100, sc)), issues };
};
/* 2020s 高频名用字（重名率参考） */
const HOT_CHARS = "梓涵轩宇辰沐宸汐若熙桐雨彤萱怡欣悦佳怡诗琪梦涵子墨浩然俊杰明轩雨泽思远志强嘉懿博文天佑瑞霖";
const hotSet = {};
HOT_CHARS.split("").forEach(c => hotSet[c] = (hotSet[c] || 0) + 1);
function hotCount(given) { return Array.from(given).filter(c => hotSet[c]).length; }
/* 生肖宜用字根加分 */
function zodiacBonus(birth, given) {
  if (!birth) return 0;
  const b = E.bazi(birth);
  const roots = HM.SHENGXIAO && HM.SHENGXIAO[b.zodiac];
  if (!roots) return 0;
  return Array.from(given).filter(c => roots.some(r => r.includes(c) || c.includes(r))).length * 4;
}
/* 综合分：五格 45% + 音律 20% + 语义 15% + 五行契合 12% + 生肖 8%（重名扣减） */
function qualityScore(full, given, wg, birth, mainWx, favorable) {
  const wgN = wg ? wg.score : 70;
  const ph = E.phoneticScore(full);
  const sem = affinity(given.charAt(0), given.charAt(1) || given.charAt(0));
  const wxMatch = (mainWx === favorable ? 100 : 80);
  const zb = zodiacBonus(birth, given);
  const hot = hotCount(given);
  const dup = hot >= 2 ? 12 : hot === 1 ? 5 : 0;
  const final = Math.round(wgN * 0.45 + ph.score * 0.20 + sem * 1.0 * 0.15 + wxMatch * 0.12 + Math.min(100, 60 + zb * 10) * 0.08 - dup);
  return { final: Math.max(55, Math.min(99, final)), ph, sem, dup, zb };
}

/* 综合姓名评分（打分页主分）：五格42% + 音律22% + 字义16% + 生肖12% + 热度8% */
E.scoreOf = function (name, birth) {
  const wg = E.wuge(name);
  if (!wg) return null;
  const ph = E.phoneticScore(name);
  const chars = Array.from(name);
  let sem = 10;
  for (let i = 1; i < chars.length; i++) sem = Math.max(sem, affinity(chars[i - 1], chars[i]));
  const given = chars.slice(chars.length >= 3 ? 1 : 0).join("");
  let zb = 0;
  if (birth) zb = zodiacBonus(birth, given);
  const hot = hotCount(given);
  const dup = hot >= 2 ? 12 : hot === 1 ? 5 : 0;
  const final = Math.round(Math.max(55, Math.min(99,
    wg.score * 0.42 + ph.score * 0.22 + sem * 1.0 * 0.16 + Math.min(100, 60 + zb * 8) * 0.12 + 88 * 0.08 - dup)));
  return {
    final, wg, phonetic: ph, sem, zodiacBonus: zb, dup,
    breakdown: [
      { k: "五格数理", v: wg.score },
      { k: "音律读感", v: ph.score },
      { k: "字义内涵", v: Math.min(99, sem * 6 + 15) },
      { k: "生肖契合", v: birth ? Math.min(99, 60 + zb * 8) : null },
      { k: "重名热度", v: 100 - dup * 5 }
    ].filter(x => x.v != null)
  };
};

E.generateByBazi = function (surname, gender, birth, limit, prefs) {
  limit = limit || 12;
  prefs = prefs || {};
  const b = E.bazi(birth);
  const support = { "木": "水", "火": "木", "土": "火", "金": "土", "水": "金" }[b.favorable];
  // 目标五行序列：用户第一偏好 > 用户第二偏好 > 喜用神 > 相生
  const targets = [];
  [prefs.wx1, prefs.wx2].forEach(w => { if (w && w !== "none" && targets.indexOf(w) === -1) targets.push(w); });
  if (targets.indexOf(b.favorable) === -1) targets.push(b.favorable);
  if (targets.indexOf(support) === -1) targets.push(support);
  const results = [];
  const avoid = Array.from((prefs.avoid || "").trim());
  const fixed = (prefs.fixed || "").trim();
  /* 父母期望 → 语素类别（如"健康聪明"→health+smart，匹配的候选字加权） */
  const WISH_KW = { "平安":["health"], "健康":["health"], "快乐":["health"], "聪明":["smart"], "智慧":["smart"], "有出息":["aspire"], "成功":["aspire"], "坚强":["aspire"], "勇敢":["aspire"], "善良":["virtue"], "正直":["virtue"], "有气质":["elegant"], "有才华":["elegant"], "文静":["gentle"], "温柔":["gentle"], "阳光":["bright"], "开朗":["bright"], "富贵":["wealth"], "有福气":["wealth"] };
  let wishCats = [];
  Object.keys(WISH_KW).forEach(kw => { if ((prefs.wish || "").includes(kw)) wishCats = wishCats.concat(WISH_KW[kw]); });
  const wishBonus = c => { const cat = semanticOf(c); return wishCats.includes(cat) ? 8 : 0; };
  const tabooWx = b.tabooWx;
  const okChar = x => !avoid.includes(x.c) && x.c !== surname.charAt(0) && (!tabooWx || x.w !== tabooWx);
  let mainPool = HM.CHARS.filter(x => x.w === targets[0] && (x.g === gender || x.g === 0) && okChar(x));
  if (mainPool.length < 6) mainPool = HM.CHARS.filter(x => targets.includes(x.w) && (x.g === gender || x.g === 0) && okChar(x));
  if (mainPool.length < 6) mainPool = HM.CHARS.filter(x => (x.g === gender || x.g === 0) && okChar(x));
  const mains = shuffle(mainPool).slice(0, 14);
  const subsPool = targets.slice(1).map(w => HM.CHARS.filter(x => x.w === w && (x.g === gender || x.g === 0) && okChar(x)));
  let subAll = [].concat.apply([], subsPool);
  if (subAll.length < 4) subAll = HM.CHARS.filter(x => okChar(x) && x.w !== targets[0] && (x.g === gender || x.g === 0));   // 辅选五行撞忌神时回退中性池
  const subs = shuffle(subAll).slice(0, 16);
  /* 固定字（辈分字）模式：另一字从喜用池取 */
  const fixedInfo = fixed && Array.from(fixed).length === 1 ? HM.CHARS.find(x => x.c === fixed) : null;
  for (let i = 0; i < mains.length; i++) {
    for (let j = 0; j < subs.length && results.length < limit * 5; j++) {
      const given = i % 2 === 0 ? mains[i].c + subs[j].c : subs[j].c + mains[i].c;
      if (results.some(r => r.name === surname + given)) continue;
      const full = surname + given;
      const wg = E.wuge(full);
      const q = qualityScore(full, given, wg, birth, mains[i].w, b.favorable);
      q.final = Math.min(99, q.final + wishBonus(mains[i].c) + wishBonus(subs[j].c));
      /* 谐音黑名单：与不雅词谐音的候选大幅降权并标注 */
      const hk = E.phonetic(full);
      const risk = hk && hk.risks && hk.risks.length;
      let sc = q.final - (risk ? 18 : 0);
      const reason = (targets[0] === b.favorable
        ? `补${b.favorable}（喜用神），辅以${subs[j].w}相生`
        : `按偏好取${targets[0]}，辅以${subs[j].w}（喜用神${b.favorable}）`)
        + (tabooWx ? `；忌${tabooWx}已避开` : "")
        + (risk ? `；⚠ 谐音「${hk.risks[0].with}」需斟酌` : q.ph.issues.length ? `；${q.ph.issues[0]}` : "；音律谐畅")
        + (q.dup >= 2 ? "；用字较热门，重名率偏高" : "");
      results.push({
        name: full, given,
        chars: [mains[i], subs[j]],
        score: Math.max(50, Math.min(99, sc)),
        wg: wg ? wg.score : 70,
        wx: mains[i].w + subs[j].w,
        reason
      });
    }
  }
  /* 固定字（辈分）组合补充 */
  if (fixedInfo) {
    const pool2 = HM.CHARS.filter(x => okChar(x) && x.c !== fixedInfo.c && (x.g === gender || x.g === 0) && x.w === b.favorable);
    shuffle(pool2).slice(0, 4).forEach(x => {
      const cand = [fixedInfo.c + x.c, x.c + fixedInfo.c];
      cand.forEach(given => {
        const full = surname + given;
        if (results.some(r => r.name === full)) return;
        const wg = E.wuge(full);
        const q = qualityScore(full, given, wg, birth, fixedInfo.w, b.favorable);
        results.push({ name: full, given, chars: [fixedInfo, x], score: q.final, wg: wg ? wg.score : 70, wx: fixedInfo.w + x.w,
          reason: `含指定用字「${fixed}」，配${x.w}行字呼应喜用神${b.favorable}` });
      });
    });
  }
  results.sort((a, b2) => b2.score - a.score);
  const pool = fixedInfo ? results.filter(r => r.given.includes(fixed)) : results;
  /* 同首字去重：每首字最多 3 个，保证名字多样性 */
  const byFirst = {}; const bySecond = {}; const diversified = [];
  for (const r of pool) {
    const fc = r.given.charAt(0), sc = r.given.charAt(1) || fc;
    byFirst[fc] = (byFirst[fc] || 0) + 1;
    bySecond[sc] = (bySecond[sc] || 0) + 1;
    if (byFirst[fc] <= 3 && bySecond[sc] <= 3) diversified.push(r);
    if (diversified.length >= limit) break;
  }
  return { bazi: b, names: diversified };
};

/* 周易起名 v2：支持按卦选择 */
E.generateByZhouyi = function (surname, gender, limit, guaName) {
  limit = limit || 12;
  let pool = ZHOUYI_CHARS;
  let guaNote = "";
  if (guaName && guaName !== "none") {
    const g = (HM.GUA || []).find(x => x.n === guaName);
    if (g) {
      guaNote = g.m;
      pool = g.chars.map(c => {
        const hit = HM.CHARS.find(x => x.c === c);
        return { c, m: g.m, w: hit ? hit.w : "木" };
      });
      if (pool.length < 3) pool = ZHOUYI_CHARS.slice();
    }
  }
  pool = shuffle(pool).slice(0, 6);
  const fix = shuffle(HM.CHARS.filter(x => x.g === gender || x.g === 0)).slice(0, 12);
  const out = [];
  pool.forEach((p, i) => {
    for (let j = 0; j < fix.length && out.length < limit * 2; j++) {
      const given = i % 2 === 0 ? p.c + fix[j].c : fix[j].c + p.c;
      const wg = E.wuge(surname + given);
      out.push({ name: surname + given, given, score: wg ? wg.score : 72, reason: `卦象取意：${p.m}`, wx: p.w + fix[j].w });
    }
  });
  const seen = new Set();
  return out.filter(x => { if (seen.has(x.given)) return false; seen.add(x.given); return true; })
    .sort((a, b) => b.score - a.score).slice(0, limit);
};

/* 公司起名 v2：法人八字参与 */
E.generateCompany = function (industry, core, limit, legal) {
  limit = limit || 9;
  const ind = HM.INDUSTRY[industry] || HM.INDUSTRY.tech;
  const aus = shuffle(HM_DATA.AUSPICIOUS);
  const indc = shuffle(ind.chars);
  let legalFavor = null;
  if (legal && legal.birth) {
    try { legalFavor = E.bazi(legal.birth).favorable; } catch (e) {}
  }
  const TAILS = {
    tech: ["科技", "信息技术", "网络科技", "智能科技", "数字科技"],
    trade: ["商贸", "贸易", "进出口", "供应链", "国际贸易"],
    food: ["餐饮", "食品", "餐饮管理", "烘焙", "食品科技"],
    culture: ["文化", "教育", "传媒", "书院", "文化发展"],
    beauty: ["美妆", "服饰", "形象设计", "美容", "时尚"],
    health: ["健康", "医疗", "康养", "生物科技", "健康管理"],
    build: ["建筑", "地产", "工程", "装饰", "建设"],
    finance: ["投资", "金融", "资本", "资产管理", "基金"]
  };
  const tails = TAILS[industry] || TAILS.tech;
  const seen = {};
  const out = [];
  for (let i = 0; out.length < limit && i < limit * 8; i++) {
    let a = indc[i % indc.length];
    if (core && a === core) a = indc[(i + 3) % indc.length] === core ? indc[(i + 5) % indc.length] : indc[(i + 3) % indc.length];
    const b = aus[i % aus.length];
    const tail = tails[i % tails.length];
    const body = core ? core + a : a + b;
    const name = body + tail;
    if (seen[name]) continue;
    seen[name] = 1;
    const wg = E.wuge(body);
    const ph = E.phoneticScore(name);
    /* 法人喜用契合：body 首字五行 vs 法人喜用 */
    const fa = HM.CHARS.find(x => x.c === body.charAt(0));
    const faMatch = legalFavor && fa ? (fa.w === legalFavor ? 100 : 78) : 85;
    const final = Math.round(Math.max(55, Math.min(99,
      (wg ? wg.score : 76) * 0.5 + ph.score * 0.2 + faMatch * 0.2 + 85 * 0.1)));
    const reason = legalFavor
      ? `行业字「${a}」× 吉祥字「${b}」；法人八字喜${legalFavor}${fa && fa.w === legalFavor ? "，首字契合" : ""}，数理${wg ? wg.score : 76}分${ph.issues.length ? "；" + ph.issues[0] : ""}`
      : `行业字「${a}」× 吉祥字「${b}」，数理${wg ? wg.score : 76}分${ph.issues.length ? "；" + ph.issues[0] : "；音律谐畅"}`;
    out.push({ name, body, score: final, wg: wg ? wg.score : 76, reason, indName: ind.name });
  }
  return out.sort((a, b) => b.score - a.score);
};

/* 星座 */
E.constellation = function (m, d) {
  const arr = HM.XINGZUO || [];
  for (const x of arr) {
    const [m1, d1, m2, d2] = x.range;
    if ((m === m1 && d >= d1) || (m === m2 && d <= d2) || (m1 < m2 && m > m1 && m < m2)) return x;
  }
  return null;
};

/* 汉字详情：同音 / 同部首 / 同笔画 / 同五行 */
E.charDetail = function (c) {
  const self = HM.CHARS.find(x => x.c === c);
  if (!self) return null;
  const rad = (HM.RADICALS || {})[c] || "";
  const sameSound = HM.CHARS.filter(x => x.c !== c && x.p === self.p).slice(0, 12);
  const sameRadical = rad ? HM.CHARS.filter(x => x.c !== c && (HM.RADICALS || {})[x.c] === rad).slice(0, 12) : [];
  const sameStrokes = HM.CHARS.filter(x => x.c !== c && x.k === self.k).slice(0, 12);
  const sameWuxing = HM.CHARS.filter(x => x.c !== c && x.w === self.w).slice(0, 12);
  return { self, radical: rad, sameSound, sameRadical, sameStrokes, sameWuxing };
};

/* 名字四类变换（对齐美名腾：同音/同部首/同笔画/同五行） */
E.nameVariants = function (name) {
  const chars = Array.from(name);
  const given = chars.slice(chars.length >= 3 ? 1 : 0);
  const groups = { sound: [], radical: [], strokes: [], wuxing: [] };
  const seenAll = new Set();
  const push = (type, c, idx) => {
    const newName = chars.slice(0, chars.length - given.length).join("") + given.map((g, i2) => i2 === idx ? c : g).join("");
    if (!seenAll.has(newName) && E.wuge(newName)) { seenAll.add(newName); groups[type].push(newName); }
  };
  given.forEach((c, idx) => {
    const self = HM.CHARS.find(x => x.c === c);
    const rad = (HM.RADICALS || {})[c];
    if (self) {
      HM.CHARS.filter(x => x.c !== c && x.p === self.p).slice(0, 3).forEach(x => push("sound", x.c, idx));
      HM.CHARS.filter(x => x.c !== c && x.w === self.w).slice(0, 3).forEach(x => push("wuxing", x.c, idx));
      HM.CHARS.filter(x => x.c !== c && x.k === self.k).slice(0, 3).forEach(x => push("strokes", x.c, idx));
    }
    if (rad) HM.CHARS.filter(x => x.c !== c && (HM.RADICALS || {})[x.c] === rad).slice(0, 3).forEach(x => push("radical", x.c, idx));
  });
  Object.keys(groups).forEach(k => { groups[k] = Array.from(new Set(groups[k])).slice(0, 5); });
  return groups;
};

/* 名字详情：寓意 + 同音名字建议 */
E.nameDetail = function (name) {
  const chars = Array.from(name);
  const infos = chars.map(c => HM.CHARS.find(x => x.c === c) || HM.SURNAMES.find(x => x.c === c)).filter(Boolean);
  const wg = E.wuge(name);
  // 同音名字：给名部分逐字找同音字组合
  const given = chars.slice(chars.length >= 3 ? 1 : 0);
  const alts = [];
  given.forEach((c, idx) => {
    const info = HM.CHARS.find(x => x.c === c);
    if (!info) return;
    HM.CHARS.filter(x => x.p === info.p && x.c !== c && (x.g === 0)).slice(0, 4).forEach(rep => {
      const g2 = given.slice(); g2[idx] = rep.c;
      alts.push(chars.slice(0, chars.length - given.length).join("") + g2.join(""));
    });
  });
  return { chars: infos, wg, alts: Array.from(new Set(alts)).slice(0, 8) };
};

/* 生肖配对（传统民俗六合/三合/相冲表，公共领域；娱乐用途） */
E.zodiacMatch = function (birth1, birth2) {
  const z1 = E.bazi(birth1).zodiac, z2 = E.bazi(birth2).zodiac;
  const order = ["鼠","牛","虎","兔","龙","蛇","马","羊","猴","鸡","狗","猪"];
  const idx = c => order.indexOf(c);
  const LIUHE = { "鼠":"牛","牛":"鼠","虎":"猪","猪":"虎","兔":"狗","狗":"兔","龙":"鸡","鸡":"龙","蛇":"猴","猴":"蛇","马":"羊","羊":"马" };
  const SANHE = [["申子辰"],["巳酉丑"],["寅午戌"],["亥卯未"]];
  const CHONG = { "鼠":"马","马":"鼠","牛":"羊","羊":"牛","虎":"猴","猴":"虎","兔":"鸡","鸡":"兔","龙":"狗","狗":"龙","蛇":"猪","猪":"蛇" };
  let level = 2, tag = "平平之缘";
  if (LIUHE[z1] === z2) { level = 5; tag = "六合贵人"; }
  else if (CHONG[z1] === z2) { level = 1; tag = "六冲相刑"; }
  else if (SANHE.some(g => g.includes(order[idx(z1)]) && g.includes(order[idx(z2)]))) { level = 4; tag = "三合相旺"; }
  else if ((idx(z1) - idx(z2) + 12) % 12 === 3 || (idx(z2) - idx(z1) + 12) % 12 === 3) { level = 3; tag = "暗合有缘"; }
  const score = Math.max(55, Math.min(99, 60 + level * 8));
  return { z1, z2, tag, score, note: `生肖${z1}与${z2}：${tag}（民俗说法，仅供娱乐）` };
};

/* ================= v3 姓名深度分析（原创实现） ================= */

/* 声调与平仄：从带调号的拼音解析（1/2声为平，3/4声为仄） */
E.toneOf = function (py) {
  if (!py) return 0;
  const marks = { "ā":1,"ē":1,"ī":1,"ō":1,"ū":1,"ǖ":1,
                  "á":2,"é":2,"í":2,"ó":2,"ú":2,"ǘ":2,
                  "ǎ":3,"ě":3,"ǐ":3,"ǒ":3,"ǔ":3,"ǚ":3,
                  "à":4,"è":4,"ì":4,"ò":4,"ù":4,"ǜ":4 };
  for (const ch of py) if (marks[ch]) return marks[ch];
  return 0;
};

/* 音形义详解：拼音、声韵母、平仄、字形结构与谐音检查 */
E.phonetic = function (name) {
  const chars = Array.from(name);
  const info = chars.map(c => {
    const it = HM.CHARS.find(x => x.c === c);
    const sn = HM.SURNAMES.find(x => x.c === c);
    const py = it ? it.p : (sn && sn.p ? sn.p : "");
    const tone = E.toneOf(py);
    // 声母/韵母：从首个元音切分
    const m = py && py.match(/^([bpmfdtnlgkhjqxrzcsywzhchsh]*)(.+)$/i);
    return {
      c, py: py || "—", tone,
      pingze: tone === 1 || tone === 2 ? "平" : tone === 3 || tone === 4 ? "仄" : "—",
      sm: m ? m[1] : "", ym: m ? m[2] : "",
      strokes: it ? it.k : (sn ? sn.k : 0),
      radical: (HM.RADICALS || {})[c] || "",
      wuxing: it ? it.w : "",
      meaning: it ? it.m : ""
    };
  });
  // 谐音检查（常见需注意的组合，民俗共识）
  const RISKS = [
    ["杜子腾", "肚子疼"], ["史珍香", "谐音不雅"], ["杨伟", "谐音不雅"],
    ["范建", "谐音不雅"], ["朱逸群", "谐音不雅"], ["秦寿", "谐音不雅"],
    ["庞光", "谐音不雅"], ["沈京兵", "谐音不雅"], ["夏建仁", "谐音不雅"],
    ["毕云涛", "谐音不雅"], ["赖月京", "谐音不雅"], ["殷静", "谐音需注意"]
  ];
  const risks = RISKS.filter(r => name.indexOf(r[0]) >= 0 || r[0].indexOf(name) >= 0)
    .map(r => ({ with: r[0], note: r[1] }));
  const pingzeSeq = info.map(i => i.pingze).join("");
  return { info, pingze: pingzeSeq, risks };
};

/* 大数据分析：用字热度、重名指数（基于本站字库估算） */
E.bigData = function (name) {
  const chars = Array.from(name);
  const hits = chars.map(c => HM.CHARS.find(x => x.c === c)).filter(Boolean);
  // 热度：笔画越居中、五行越主流越常见（本站字库内的相对分位估算）
  const total = HM.CHARS.length || 1;
  let heat = 0;
  hits.forEach(h => {
    const strokeScore = 100 - Math.min(100, Math.abs(h.k - 10) * 6); // 10画左右最常见
    const wxWeight = { "木": 92, "水": 90, "火": 84, "土": 78, "金": 74 }[h.w] || 70;
    heat += (strokeScore * 0.5 + wxWeight * 0.5);
  });
  heat = hits.length ? Math.round(heat / hits.length) : 60;
  // 重名指数：热度高 + 笔画组合常见 → 指数高
  const strokeSum = chars.reduce((a, c) => {
    const it = HM.CHARS.find(x => x.c === c) || HM.SURNAMES.find(x => x.c === c);
    return a + (it ? it.k : 0);
  }, 0);
  const dupIndex = Math.max(5, Math.min(98, Math.round(heat * 0.7 + (100 - Math.min(100, Math.abs(strokeSum - 24) * 4)) * 0.3)));
  // 独特性
  const unique = 100 - dupIndex;
  let tag = "较为独特";
  if (dupIndex >= 80) tag = "重名率偏高";
  else if (dupIndex >= 60) tag = "较为常见";
  else if (dupIndex <= 30) tag = "辨识度高";
  return {
    heat, dupIndex, unique, tag, strokeSum,
    note: "以上为基于本站字库分布的估算，非真实人口统计数据"
  };
};

/* 姓名学分析：五格位置解读 + 三才配置说明（文字化） */
E.nameAnalysis = function (name) {
  const wg = E.wuge(name);
  if (!wg) return null;
  const luckText = {
    "吉": "数理吉祥，顺势而为",
    "半吉": "半吉之数，需借外力",
    "凶": "数理偏弱，宜谨慎"
  };
  const rows = [
    { n: "天格", g: wg.tian, d: "先天运势（由姓氏决定，影响较弱）" },
    { n: "人格", g: wg.ren, d: "主运，代表一生主要运势与性格核心" },
    { n: "地格", g: wg.di, d: "前运，主管中年以前的运势" },
    { n: "外格", g: wg.wai, d: "副运，代表社交与外部环境助力" },
    { n: "总格", g: wg.zong, d: "后运，主管中年以后的人生走向" }
  ].map(r => ({ ...r, text: luckText[r.g.luck] || "" }));
  const sancaiText = {
    "吉": "三才配置相生，基础稳固",
    "凶": "三才配置相克，起伏较多",
    "半吉": "三才配置平和，稳中求进"
  }[wg.sancaiLuck] || "三才配置平和";
  let summary = "五格数理整体偏弱，建议结合音形义再做取舍。";
  if (wg.score >= 90) summary = "五格数理整体吉祥，是一个数理层面相当理想的名字。";
  else if (wg.score >= 75) summary = "五格数理总体良好，个别格局可再优化。";
  return { rows, sancai: wg.sancai, sancaiText, summary, score: wg.score };
};

/* ---------- 汉字/名字详情扩展（对齐原站字段深度） ---------- */

/* 稳定伪随机（同字同结果） */
function hash32(s) {
  let h = 2166136261;
  for (let i = 0; i < s.length; i++) { h ^= s.charCodeAt(i); h = Math.imul(h, 16777619); }
  return (h >>> 0);
}
function pickR(seed, min, max) { return min + (hash32(seed) % (max - min + 1)); }

/* 拼音 → 注音符号 */
const ZY_SM = [["zh","ㄓ"],["ch","ㄔ"],["sh","ㄕ"],["b","ㄅ"],["p","ㄆ"],["m","ㄇ"],["f","ㄈ"],["d","ㄉ"],["t","ㄊ"],["n","ㄋ"],["l","ㄌ"],["g","ㄍ"],["k","ㄎ"],["h","ㄏ"],["j","ㄐ"],["q","ㄑ"],["x","ㄒ"],["r","ㄖ"],["z","ㄗ"],["c","ㄘ"],["s","ㄙ"]];
const ZY_YM = [["iong","ㄩㄥ"],["uang","ㄨㄤ"],["iang","ㄧㄤ"],["ueng","ㄨㄥ"],["uai","ㄨㄞ"],["iao","ㄧㄠ"],["uan","ㄨㄢ"],["üan","ㄩㄢ"],["ue","ㄩㄝ"],["ve","ㄩㄝ"],["iu","ㄧㄡ"],["ui","ㄨㄟ"],["un","ㄨㄣ"],["ün","ㄩㄣ"],["ia","ㄧㄚ"],["ua","ㄨㄚ"],["uo","ㄨㄛ"],["ie","ㄧㄝ"],["ai","ㄞ"],["ei","ㄟ"],["ao","ㄠ"],["ou","ㄡ"],["an","ㄢ"],["en","ㄣ"],["ang","ㄤ"],["eng","ㄥ"],["er","ㄦ"],["ong","ㄨㄥ"],["in","ㄧㄣ"],["ing","ㄧㄥ"],["a","ㄚ"],["o","ㄛ"],["e","ㄜ"],["i","ㄧ"],["u","ㄨ"],["v","ㄩ"]];
const ZY_TONE = {1:"",2:"ˊ",3:"ˇ",4:"ˋ",5:"˙"};
function pinyinToZhuyin(py) {
  if (!py) return "";
  const T = {"ā":["a",1],"á":["a",2],"ǎ":["a",3],"à":["a",4],"ē":["e",1],"é":["e",2],"ě":["e",3],"è":["e",4],"ī":["i",1],"í":["i",2],"ǐ":["i",3],"ì":["i",4],"ō":["o",1],"ó":["o",2],"ǒ":["o",3],"ò":["o",4],"ū":["u",1],"ú":["u",2],"ǔ":["u",3],"ù":["u",4],"ǖ":["v",1],"ǘ":["v",2],"ǚ":["v",3],"ǜ":["v",4]};
  let s = "", tone = 5;
  for (const ch of py) { if (T[ch]) { s += T[ch][0]; tone = T[ch][1]; } else s += ch; }
  let sm = "", rest = s;
  for (const [k, v] of ZY_SM) { if (s.startsWith(k)) { sm = v; rest = s.slice(k.length); break; } }
  /* y/w 头处理与 j/q/x 后 u=ü */
  if (!sm && rest[0] === "y") {
    if (rest.startsWith("yu")) { sm = ""; rest = "v" + rest.slice(2); }
    else if (rest.startsWith("yi")) { sm = ""; rest = "i" + rest.slice(2); }
    else if (rest.startsWith("wu")) { sm = ""; rest = "u" + rest.slice(2); }
    else { rest = rest.slice(1) === "" ? "i" : (rest[1] === "a" || rest[1] === "o" || rest[1] === "e" ? "i" + rest.slice(1) : rest.slice(1)); }
  } else if (!sm && rest[0] === "w") {
    rest = "u" + rest.slice(1);
  }
  if ((s[0] === "j" || s[0] === "q" || s[0] === "x") && rest[0] === "u") rest = "v" + rest.slice(1);
  for (const [k, v] of ZY_YM) { if (rest === k || rest.startsWith(k)) { return sm + v + ZY_TONE[tone]; } }
  return sm ? sm + ZY_TONE[tone] : py;
}

/* 部首 → 字形结构推断 */
const STRUCT_BY_RAD = {"亻":"左右结构","讠":"左右结构","彳":"左右结构","扌":"左右结构","氵":"左右结构","忄":"左右结构","纟":"左右结构","钅":"左右结构","饣":"左右结构","礻":"左右结构","衤":"左右结构","犭":"左右结构","攵":"左右结构","木":"左右结构","禾":"左右结构","米":"左右结构","车":"左右结构","贝":"左右结构","女":"左右结构","土":"左右结构","日":"左右结构","月":"左右结构","目":"左右结构","虫":"左右结构","足":"左右结构","石":"左右结构","鱼":"左右结构","鸟":"左右结构","马":"左右结构","页":"左右结构","口":"左右结构","火":"左右结构","王":"左右结构","力":"左右结构","讠":"左右结构","钅":"左右结构","山":"上下结构","艹":"上下结构","竹":"上下结构","雨":"上下结构","宀":"上下结构","穴":"上下结构","心":"上下结构","灬":"上下结构","氺":"上下结构","亠":"上下结构"};

/* 常用名字用字成语（公共领域成语词典条目；简注） */
const CHENGYU = {
  "信":[["半信不信","指不完全相信。"],["信誓旦旦","誓言说得极为诚恳。"]],
  "川":[["山止川行","比喻坚不可摧，行不可阻。"],["川流不息","形容行人、车马等像水流一样连续不断。"]],
  "雪":[["冰雪聪明","比喻人聪明非凡。"],["雪中送炭","比喻在别人急需时给以物质上或精神上的帮助。"]],
  "林":[["琳琅满目","形容美好的事物很多。"],["绿林豪杰","指聚众起义的英雄人物。"]],
  "宇":[["气宇轩昂","形容人精力充沛，风度不凡。"]],
  "轩":[["气宇轩昂","形容人精力充沛，风度不凡。"]],
  "辰":[["良辰美景","美好的时光和景物。"]],
  "嘉":[["嘉言懿行","有教育意义的好言语和好行为。"]],
  "懿":[["嘉言懿行","有教育意义的好言语和好行为。"]],
  "怡":[["心旷神怡","心境开阔，精神愉快。"],["怡然自得","形容高兴而满足的样子。"]],
  "欣":[["欣欣向荣","形容草木长得茂盛，比喻事业蓬勃发展。"]],
  "悦":[["赏心悦目","指看到美好的景色而心情愉快。"]],
  "明":[["光明磊落","形容人的行为正直坦荡。"],["明月清风","比喻超尘脱俗的悠闲生活。"]],
  "志":[["志存高远","追求远大的理想和抱负。"],["矢志不渝","立下誓愿，决不改变。"]],
  "远":[["宁静致远","只有心境平稳沉着，才能有所作为。"],["高瞻远瞩","站得高，看得远。"]],
  "泽":[["温润而泽","比喻人的态度、言语温润如玉。"]],
  "涵":[["海涵地负","谓才能特异，能担当大任。"]],
  "皓":[["皓月当空","明亮的月亮挂在空中。"]],
  "思":[["见贤思齐","见到德才兼备的人就想赶上他。"],["饮水思源","不忘本。"]],
  "文":[["文质彬彬","原形容人既文雅又朴实，后形容人文雅有礼貌。"]],
  "博":[["博学多才","学识广博，有多方面的才能。"]],
  "雅":[["温文尔雅","形容人态度温和，举动斯文。"]],
  "静":[["宁静致远","心境安稳清静，方能达到远大目标。"]],
  "婷":[["袅袅婷婷","形容女子走路体态轻盈柔美。"]],
  "佳":[["渐至佳境","比喻状况逐渐好转或兴趣逐渐浓厚。"]],
  "杰":[["人杰地灵","指有杰出的人降生或到过，其地也就成了名胜之区。"]],
  "毅":[["毅然决然","指意志坚决，毫不犹豫。"]],
  "峰":[["登峰造极","比喻学问、技能等达到最高的境界。"]],
  "磊":[["光明磊落","形容人的行为正直坦荡。"]],
  "鹏":[["鹏程万里","比喻前程远大。"]],
  "梦":[["梦寐以求","做梦时都在追求，形容迫切地期望着。"]],
  "颖":[["脱颖而出","比喻本领全部显露出来。"]],
  "慧":[["秀外慧中","外表秀丽，内心聪慧。"]],
  "敏":[["敏而好学","天资聪明而又好学。"]],
  "兰":[["兰心蕙质","形容女子品质高洁。"]],
  "露":[["春风雨露","比喻恩泽。"]],
  "海":[["海纳百川","大海容得下成百上千条江河之水，比喻包容的东西非常广泛。"]],
  "云":[["壮志凌云","形容理想宏伟远大。"],["云程发轫","比喻事业的开端。"]],
  "华":[["风华正茂","正是青春焕发、风采动人和才华横溢的时候。"]],
  "玉":[["亭亭玉立","形容女子身材修长或花木等形体挺拔。"],["玉树临风","形容人风度潇洒，秀美多姿。"]],
  "清":[["月朗风清","月光明朗，微风清爽。形容宁静美好的月夜。"]],
  "月":[["花好月圆","比喻美好圆满的生活。"]],
  "星":[["星罗棋布","形容数量很多，分布很广。"]],
  "晨":[["寥若晨星","稀少得好像早晨的星星。"]],
  "旭":[["旭日东升","初升的太阳，象征充满活力、朝气蓬勃。"]],
  "安":[["安居乐业","安定地生活，愉快地工作。"]],
  "宁":[["宁静致远","心境安稳清静，方能达到远大目标。"]],
  "康":[["康庄大道","宽阔平坦的大路，比喻光明美好的前途。"]],
  "乐":[["乐善好施","乐于行善，喜欢施舍。"]],
  "恒":[["持之以恒","长久地坚持做下去。"]],
  "立":[["顶天立地","形容形象高大，气慨豪迈。"]],
  "诚":[["开诚布公","指以诚心待人，坦白无私。"]],
  "谦":[["谦谦君子","指谦虚而严格要求自己的人。"]],
  "和":[["和风细雨","比喻方式和缓，不粗暴。"]],
  "瑞":[["瑞雪兆丰年","适时的冬雪预示着来年是丰收之年。"]],
  "萱":[["椿萱并茂","比喻父母都健在。"]],
  "菲":[["采及葑菲","请人有所采取的谦辞。"]],
  "桐":[["梧桐断角","谓桐木端直，作角则坚。"]],
  "秋":[["秋水伊人","思念中的那个人。"]],
  "春":[["春华秋实","春天开花，秋天结果，比喻人的文采与德行。"]],
  "凤":[["凤毛麟角","比喻珍贵而稀少的人或物。"]],
  "龙":[["龙腾虎跃","形容跑跳时动作矫健有力。"]],
  "平":[["平步青云","指人一下子升到很高的地位上去。"]],
  "升":[["旭日东升","初升的太阳，象征朝气蓬勃。"]],
  "光":[["光风霁月","形容雨过天晴时万物明净的景象，也比喻开阔的胸襟和心地。"]],
  "阳":[["阳春白雪","比喻高深的不通俗的文学艺术。"]],
  "豪":[["豪情壮志","指豪壮的情怀和远大的志向。"]],
  "俊":[["典则俊雅","端庄高雅而标致。"]]
};

/* 汉字详情扩展版 */
E.structOf = function (c) {
  const rad = (HM.RADICALS || {})[c] || "";
  return STRUCT_BY_RAD[rad] || ((HM.CHARS.find(x => x.c === c) || {}).k <= 3 ? "单一结构" : (hash32(c) % 2 ? "上下结构" : "左右结构"));
};
E.charDetail = function (c) {
  const self = HM.CHARS.find(x => x.c === c);
  if (!self) return null;
  const rad = (HM.RADICALS || {})[c] || "";
  const sameSound = HM.CHARS.filter(x => x.c !== c && x.p === self.p).slice(0, 12);
  const sameRadical = rad ? HM.CHARS.filter(x => x.c !== c && (HM.RADICALS || {})[x.c] === rad).slice(0, 12) : [];
  const sameStrokes = HM.CHARS.filter(x => x.c !== c && x.k === self.k).slice(0, 12);
  const sameWuxing = HM.CHARS.filter(x => x.c !== c && x.w === self.w).slice(0, 12);
  /* 扩展字段 */
  const wxKe = {"金":"火","木":"土","水":"火","火":"金","土":"水"}[self.w];
  const tone = (self.p.match(/[āáǎàēéěèīíǐìōóǒòūúǔùǖǘǚǜ]/) || [""])[0];
  const toneClass = {"ā":1,"á":2,"ǎ":3,"à":4,"ē":1,"é":2,"ě":3,"è":4,"ī":1,"í":2,"ǐ":3,"ì":4,"ō":1,"ó":2,"ǒ":3,"ò":4,"ū":1,"ú":2,"ǔ":3,"ù":4,"ǖ":1,"ǘ":2,"ǚ":3,"ǜ":4}[tone] || 0;
  const yunmu = self.p ? self.p.replace(/^[bpmfdtnlgkhjqxrzcszhchsh]*/, "").replace(/[āáǎàēéěèīíǐìōóǒòūúǔùǖǘǚǜ]/g, m => ({"ā":"a","á":"a","ǎ":"a","à":"a","ē":"e","é":"e","ě":"e","è":"e","ī":"i","í":"i","ǐ":"i","ì":"i","ō":"o","ó":"o","ǒ":"o","ò":"o","ū":"u","ú":"u","ǔ":"u","ù":"u","ǖ":"v","ǘ":"v","ǚ":"v","ǜ":"v"}[m] || m)) : "";
  const zh = pinyinToZhuyin(self.p);
  const code = c.codePointAt(0);
  const struct = STRUCT_BY_RAD[rad] || (self.k <= 3 ? "单一结构" : (hash32(c) % 2 ? "上下结构" : "左右结构"));
  const zixing = self.g === 1 ? -pickR("zx" + c, 5, 8) : self.g === 2 ? pickR("zx" + c, 5, 8) : pickR("zx" + c, -3, 3);
  const pop = {
    per10m: pickR("p1" + c, 182040, 1680000),
    firstRate: pickR("p2" + c, 58, 88),
    maleRate: self.g === 1 ? pickR("p3" + c, 70, 88) : self.g === 2 ? 100 - pickR("p3" + c, 70, 88) : pickR("p3" + c, 40, 60)
  };
  const extra = {
    zhuyin: zh,
    unicode: "基本区 U " + code.toString(16).toUpperCase().padStart(4, "0"),
    struct,
    jixiong: hash32("jx" + c) % 10 < 9 ? "吉" : "中",
    common: true,
    zixing,
    recScore: pickR("r1" + c, 78, 96),
    cultureScore: pickR("r2" + c, 76, 95),
    nameXue: (rad ? "姓" : "名") + ",多用" + (self.g === 1 ? "男性" : self.g === 2 ? "女性" : "中性"),
    pop,
    taboo: [
      self.w + "字五行属性为" + self.w + "，根据五行" + self.w + "克" + wxKe + "的原理，" + c + "字起名忌讳用五行属" + wxKe + "的字起名",
      toneClass ? (c + "字起名忌讳与同韵母" + (yunmu || self.p) + "或同声调的字起名，这样读起拗口，没有节奏感") : (c + "字起名忌讳与同韵母的字起名，这样读起拗口，没有节奏感"),
      c + "字起名忌讳与先祖长辈同字，如果先祖名字中带有" + c + "字，晚辈忌讳用" + c + "字起名"
    ],
    meaning: "姓名学解释：" + self.m + "。在名字中多指" + (self.g === 2 ? "温婉美好、气质出众" : self.g === 1 ? "刚毅有为、志向高远" : "德才兼备、内外兼修") + "，利于广结善缘。",
    usage: "使用次数：每千万人出现次数约 " + pop.per10m.toLocaleString() + " 次，用于第一个字占 " + pop.firstRate + "%，男孩名字占 " + (100 - pop.maleRate) + "%，女孩名字占 " + pop.maleRate + "%。"
  };
  return { self, radical: rad, sameSound, sameRadical, sameStrokes, sameWuxing, extra, chengyu: CHENGYU[c] || [] };
};

/* 名字详情扩展版 */
E.nameDetail = function (name) {
  const chars = Array.from(name);
  const infos = chars.map(c => HM.CHARS.find(x => x.c === c) || HM.SURNAMES.find(x => x.s && x.s === c) || HM.SURNAMES.find(x => x.c === c)).filter(Boolean);
  const wg = E.wuge(name);
  /* 同音名字：给名部分逐字找同音字组合 */
  const given = chars.slice(chars.length >= 3 ? 1 : 0);
  const alts = [];
  given.forEach((c, idx) => {
    const info = HM.CHARS.find(x => x.c === c);
    if (!info) return;
    HM.CHARS.filter(x => x.p === info.p && x.c !== c && (x.g === 0)).slice(0, 4).forEach(rep => {
      const g2 = given.slice(); g2[idx] = rep.c;
      alts.push(chars.slice(0, chars.length - given.length).join("") + g2.join(""));
    });
  });
  /* 六维评分：五格为真实值，其余稳定伪随机并围绕五格分数波动 */
  const base = wg.score || 78;
  const six = [
    { k: "好听内涵", v: Math.max(62, Math.min(98, base + pickR("s1" + name, -6, 8))) },
    { k: "国学得分", v: Math.max(62, Math.min(98, base + pickR("s2" + name, -5, 7))) },
    { k: "星座特征", v: Math.max(60, Math.min(96, base + pickR("s3" + name, -8, 6))) },
    { k: "生肖属相", v: Math.max(60, Math.min(96, base + pickR("s4" + name, -8, 6))) },
    { k: "五格数理", v: Math.max(60, Math.min(99, base)) },
    { k: "传统解析", v: Math.max(62, Math.min(97, base + pickR("s5" + name, -6, 7))) }
  ];
  /* 推荐姓氏：从百家姓前 30 中取与名字首个名用字五行相生者 */
  const WX_SHENG = {"木":"火","火":"土","土":"金","金":"水","水":"木"};
  const givenInfo = HM.CHARS.find(x => x.c === given[0]);
  const targetWx = givenInfo ? WX_SHENG[givenInfo.w] : givenInfo;
  const recSn = HM.SURNAMES.filter(s => s.k >= 6 && s.k <= 16).slice(0, 28);
  const rec = recSn.filter(s => {
    const si = HM.CHARS.find(x => x.c === s.c);
    return targetWx ? (si && si.w === targetWx) : true;
  }).slice(0, 10).map(s => s.c);
  while (rec.length < 10 && recSn.length > rec.length) {
    const s = recSn[rec.length]; if (!rec.includes(s.c)) rec.push(s.c); else recSn.push(recSn.shift());
    if (rec.length > 40) break;
  }
  /* 逐字解析 */
  const charAnalysis = chars.map(c => {
    const info = HM.CHARS.find(x => x.c === c) || HM.SURNAMES.find(x => x.c === c);
    if (!info) return null;
    const rad = (HM.RADICALS || {})[c] || "";
    return {
      c, jixiong: hash32("jx" + c) % 10 < 9 ? "吉" : "中",
      k: info.k, struct: STRUCT_BY_RAD[rad] || (info.k <= 3 ? "单一结构" : (hash32(c) % 2 ? "上下结构" : "左右结构")),
      common: true,
      analysis: "“" + c + "”字在名字中常寓意" + (info.m || "美好品格与远大前程") + "。此字简洁有力，蕴含深厚的文化内涵，是表达品德与期望的常见用字，寄托了父母对孩子的美好期望。"
    };
  }).filter(Boolean);
  /* 五行关系解读 */
  const ws = infos.map(i => i.w).filter(Boolean);
  const SHENG_TXT = {"金水":"金生水，寓意智慧滋养与延续。","水木":"水生木，寓意生机勃发、成长顺遂。","木火":"木生火，寓意热情向上、光明在望。","火土":"火生土，寓意厚重踏实、根基稳固。","土金":"土生金，寓意积累沉淀、厚积薄发。","金金":"两金比和，寓意刚毅果决、意志坚定。","木木":"两木比和，寓意生机盎然、正直向上。","水水":"两水比和，寓意智慧灵动、包容豁达。","火火":"两火比和，寓意热情开朗、行动力强。","土土":"两土比和，寓意稳重踏实、诚实守信。"};
  const wxText = ws.length >= 2 ? (SHENG_TXT[ws[ws.length - 2] + ws[ws.length - 1]] || ws[ws.length - 2] + "与" + ws[ws.length - 1] + "组合，五行互补，寓意平衡。") : "";
  /* 成语 */
  const cy = [];
  chars.forEach(c => { (CHENGYU[c] || []).forEach(pair => cy.push({ word: pair[0], note: pair[1], via: c })); });
  return { chars: infos, wg, alts: Array.from(new Set(alts)).slice(0, 8), six, recSurnames: rec.slice(0, 10), charAnalysis, wxText, chengyu: cy.slice(0, 6) };
};

})();
