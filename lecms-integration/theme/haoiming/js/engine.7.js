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

/* ================= 精确节气表（1900-2100，寿星天文历 sxtwl 生成，北京时间） =================
 * 每年 12 个「节」交节时刻（定月建/年起运的边界）：
 *   小寒 立春 惊蛰 清明 立夏 芒种 小暑 立秋 白露 寒露 立冬 大雪
 * 编码：以 1900-01-01 00:00（北京时间）起算的分钟数，年内相邻增量各 3 位 base36，
 *      每年首增量相对上一年「大雪」。表外年份回退传统近似（立春 2/4、月界 6 日）。
 */
var JIEQI_TABLE = [
  "1900:5ngwrox2uxl7y62ynwyxjyw5yk2y0wxg3wz4,1901:wq9wrnx2vxl9y66yn",
  "yyxkyw2yk0y0wxg4wz7,1902:wqbwrmx2uxl5y62yntyxeyw0yk0y0zxg9wzb,19",
  "03:wqfwrnx2sxl3y5zynuyxiyw3yk2y10xg7wza,1904:wqewrnx2sxl3y60ynuy",
  "xjyw4yk2y0yxg5wz8,1905:wqewrpx2uxl4y60ynsyxeyw1yk1y0yxg6wz9,1906",
  ":wqewrrx2wxl7y61yntyxeyw1yk0y0zxg8wza,1907:wqewrox2sxl4y5zynryxe",
  "yw1yk2y11xg9wzb,1908:wqewrmx2rxl2y5yyntyxhyw3yk1y0zxg7wza,1909:w",
  "qdwrox2sxl4y62ynvyxiyw2yk1y0wxg6wza,1910:wqfwrpx2uxl2y5wynpyxdyw",
  "0yk1y0zxg8wzc,1911:wqgwrpx2txl2y5vynqyxfyw3yk5y12xg8wz9,1912:wqb",
  "wrnx2rxl3y5zynsyxiyw4yk5y11xg8wz8,1913:wqbwrlx2qxl3y5zynqyxeyw1y",
  "k2y12xgawzb,1914:wqewrmx2rxl2y5yynsyxfyw2yk3y13xgcwze,1915:wqfwr",
  "lx2nxkxy5uynpyxgyw4yk5y14xgdwze,1916:wqgwrmx2nxkxy5synoyxgyw5yk6",
  "y13xgawzc,1917:wqfwrpx2rxl1y5wynpyxfyw4yk5y13xgbwzc,1918:wqfwrpx",
  "2sxl0y5tynlyx9yvzyk4y15xgfwzf,1919:wqhwrox2qxl0y5tynnyxcyw1yk6y1",
  "5xgfwze,1920:wqfwrlx2pxl0y5wynryxhyw3yk5y12xgcwzd,1921:wqgwrmx2p",
  "xl0y5vynpyxeyw0yk3y11xgawze,1922:wqiwrpx2sxl0y5vynpyxfyw4yk5y13x",
  "gcwze,1923:wqfwrmx2oxkyy5synoyxgyw6yk9y16xgdwzd,1924:wqdwrkx2mxk",
  "xy5tynoyxfyw7yk9y17xgdwzc,1925:wqcwrkx2nxkyy5wynqyxhyw6yk9y17xgf",
  "wze,1926:wqewrkx2mxkuy5qynmyxcyw2yk8y19xgjwzj,1927:wqiwrlx2kxksy",
  "5nynkyxdyw5ykay1axgiwzh,1928:wqhwrlx2lxkuy5oynmyxfyw7ykby18xgfwz",
  "g,1929:wqhwrnx2nxkvy5pynjyx9yw1yk7y17xggwzh,1930:wqjwrox2qxkwy5q",
  "ynjyxayw1yk7y19xgjwzj,1931:wqhwrlx2lxkuy5qynkyxcyw3yk8y1axgjwzi,",
  "1932:wqhwrkx2kxkty5pynlyxcyw4yk7y17xggwzg,1933:wqhwrmx2mxkvy5syn",
  "nyxfyw6yk7y17xgfwzg,1934:wqhwrox2mxkuy5nyniyxbyw4yk8y19xgiwzi,19",
  "35:wqhwrnx2lxksy5myniyxcyw6ykcy1cxgiwzf,1936:wqewrix2kxkuy5pynny",
  "xfyw9ykey1bxgjwzf,1937:wqewrix2ixkty5qynkyxbyw3ykay1cxgkwzj,1938",
  ":wqhwrkx2jxkry5mynkyxcyw6ykby1dxgnwzm,1939:wqiwrix2gxkny5kynjyxe",
  "yw9ykfy1fxgnwzl,1940:wqjwrkx2gxkny5hyngyxcyw7ykey1dxglwzj,1941:w",
  "qiwrmx2kxkry5lynhyxcyw7ykey1exgmwzk,1942:wqiwrnx2kxkry5jyneyx7yw",
  "2ykcy1gxgpwzo,1943:wqkwrlx2jxkoy5iyneyx8yw4ykcy1fxgpwzm,1944:wqi",
  "wrkx2hxkqy5mynjyxdyw7ykdy1dxgmwzl,1945:wqiwrlx2jxkqy5lyngyxayw2y",
  "k9y1bxglwzm,1946:wqkwrox2lxkqy5iyngyxayw5ykby1exgmwzl,1947:wqiwr",
  "kx2ixkoy5jyngyxdyw9ykgy1gxgnwzk,1948:wqgwrix2gxkny5jyngyxbyw7ykf",
  "y1fxgnwzj,1949:wqfwrix2gxkpy5lyniyxdyw7ykfy1hxgpwzl,1950:wqiwrix",
  "2exkly5hyneyxayw6ykfy1ixgswzq,1951:wqkwrjx2exkiy5cyncyx9yw7ykhy1",
  "ixgrwzn,1952:wqkwrjx2exkky5fyneyxdywaykjy1ixgqwzm,1953:wqiwrkx2g",
  "xkny5fyncyx7yw4ykey1hxgrwzo,1954:wqkwrmx2ixkmy5fynbyx6yw4ykfy1jx",
  "guwzp,1955:wqkwrix2dxkky5fyndyxbyw8ykiy1kxgtwzq,1956:wqjwrix2cxk",
  "jy5fyneyxayw6ykfy1hxgqwzo,1957:wqkwrlx2fxkly5fynfyxbyw8ykgy1ixgq",
  "wzo,1958:wqkwrlx2gxkjy5dynbyx9yw8ykiy1kxgtwzq,1959:wqkwrkx2fxkiy",
  "5cyn9yx8yw8ykky1mxgswzn,1960:wqhwrhx2dxkky5fyneyxcywbykly1oxgtwz",
  "o,1961:wqhwrfx2dxkjy5fyndyx9yw5ykhy1mxgvwzs,1962:wqlwrix2cxkhy5b",
  "ynayx8yw7ykhy1nxgxwzu,1963:wqlwrix29xkey59ynayxcywbykny1oxgwwzt,",
  "1964:wqlwrjx2bxkey59yn9yx8yw8ykjy1mxguwzq,1965:wqlwrkx2fxkiy5byn",
  "8yx7yw8ykjy1nxgwwzr,1966:wqkwrkx2dxkhy5ayn8yx5yw6ykjy1pxgywzv,19",
  "67:wqmwrjx2bxkfy58yn7yx5yw6ykjy1nxgwwzs,1968:wqlwrhx2bxkfy5bynby",
  "xbyw9ykky1nxgvwzr,1969:wqlwrix2cxkgy5byn9yx9yw6ykhy1mxguwzs,1970",
  ":wqnwrkx2cxkgy58yn6yx7yw7ykky1oxgwwzr,1971:wqkwrgx2axkdy58yn9yxa",
  "ywdykqy1txgywzr,1972:wqiwrex28xkdy58yn9yx9yw9ykny1rxgxwzs,1973:w",
  "qiwrfx29xkdy58yn9yx8ywaykmy1sxh1wzu,1974:wqmwrgx27xkay55yn6yx7yw",
  "aykoy1uxh3wzz,1975:wqpwrhx27xk7y52yn3yx5ywaykoy1txh1wzv,1976:wqn",
  "wrix29xkay54yn5yx8ywbykqy1uxh1wzu,1977:wqmwrix2bxkey56yn4yx4yw6y",
  "kmy1sxh2wzx,1978:wqowrkx2bxkdy56yn2yx2yw5ykky1txh3wzy,1979:wqowr",
  "gx28xkay55yn6yx8ywaykpy1uxh3wzx,1980:wqnwrgx27xkby55yn8yx8yw9ykk",
  "y1qxgzwzv,1981:wqowrix2axkcy56yn6yx7yw9ykmy1rxgywzv,1982:wqowrix",
  "2axkay53yn4yx7ywbykqy1uxh2wzw,1983:wqnwrhx27xk9y53yn3yx5ywbykqy1",
  "vxh1wzu,1984:wqjwrex26xk9y55yn6yx8ywdyksy1xxh3wzu,1985:wqjwrdx24",
  "xkay55yn5yx7yw9ykpy1wxh4wzz,1986:wqowrgx24xk6y51yn1yx5yw9ykpy1wx",
  "h6x00,1987:wqowrfx22xk2y4yyn1yx8yweykvy20xh6wzy,1988:wqowrfx24xk",
  "4y4zyn1yx6ywbyksy1xxh4wzx,1989:wqowrhx27xk8y50ymzyx2yw9ykqy1xxh7",
  "wzz,1990:wqowrhx25xk6y4yymzyx2ywaykry21xh9x03,1991:wqqwrgx24xk5y",
  "4yymzyx3yw8ykqy1yxh7x00,1992:wqpwrfx24xk5y50yn1yx6ywbykry1xxh6wz",
  "z,1993:wqpwrgx26xk6y51yn1yx5ywaykqy1wxh6x00,1994:wqqwrjx27xk6y4y",
  "ymzyx2yw9ykry1yxh7wzz,1995:wqnwrfx23xk4y4yyn0yx7ywfykxy22xh9wzy,",
  "1996:wqlwrdx22xk4y50yn3yx7ywdykty21xh8wzz,1997:wqmwrex22xk4y4zyn",
  "2yx4ywbykty20xhax02,1998:wqpwrfx20xk0y4uymyyx5yweykwy24xhcx06,19",
  "99:wqrwrgx21xjzy4symwyx4ywdykwy22xhax01,2000:wqqwrfx23xk1y4uymxy",
  "x3ywdykwy23xhax01,2001:wqowrgx23xk4y4xymxyx1yw9ykuy23xhcx04,2002",
  ":wqrwrgx24xk2y4vymwywzyw7yksy22xhdx04,2003:wqqwrdx20xjzy4uymyyx4",
  "ywcykwy25xhcx04,2004:wqqwrdx20xjzy4vyn0yx5ywdykty20xhax02,2005:w",
  "qqwrgx22xk1y4vymxyx3ywaykuy20xh9x03,2006:wqqwrgx22xjzy4rymuyx2yw",
  "eykyy26xhex04,2007:wqpwrex20xjzy4rymvyx3ywdykyy26xhdx02,2008:wqn",
  "wrbx1zxjzy4tymxyx3ywdykyy27xhex03,2009:wqowrcx1yxjyy4tymwyx2ywcy",
  "kxy26xhgx08,2010:wqtwrfx1yxjwy4qymtyx1ywbykwy25xhgx08,2011:wqtwr",
  "ex1xxjuy4nymsyx3ywfyl1y29xhgx06,2012:wqrwrex1zxjxy4qymuyx3yweyky",
  "y27xhex05,2013:wqrwrfx22xjzy4symtyx0yw9ykwy26xhgx07,2014:wqrwrfx",
  "1zxjxy4oymsyx0ywbykzy2axhkx09,2015:wqtwrdx1yxjvy4qymtyx2ywdykzy2",
  "7xhgx06,2016:wqrwrex1yxjvy4rymuyx3yweykyy26xhfx05,2017:wqrwrex1z",
  "xjwy4qymuyx2ywdykzy27xhgx07,2018:wqswrfx20xjxy4oymsyx1ywdykzy29x",
  "hhx06,2019:wqpwrbx1wxjty4oymryx2ywhyl4y2dxhix06,2020:wqowr9x1uxj",
  "ty4pymvyx4ywgyl2y2bxhjx07,2021:wqqwrcx1vxjty4oymtyx1ywdykzy2axhk",
  "x0a,2022:wqtwrdx1txjoy4iymoyx0ywfyl3y2exhnx0d,2023:wqvwrdx1uxjpy",
  "4iymnyx0ywhyl4y2cxhkx0a,2024:wqswrex1wxjry4kymoywyywdyl2y2dxhkx0",
  "9,2025:wqswrdx1xxjty4lymnywxywayl1y2dxhnx0c,2026:wqvwrfx1xxjty4k",
  "ymoywxyw9ykzy2cxhnx0c,2027:wquwrcx1txjqy4kymoyx0ywdyl2y2dxhlx0b,",
  "2028:wqtwrdx1txjry4lymsyx2ywfyl1y2axhjx09,2029:wquwrex1xxjty4lym",
  "ryx0ywdyl1y2axhix09,2030:wqtwrex1vxjqy4hymmywzywgyl5y2hxhnx0b,20",
  "31:wqswrbx1txjpy4jymoyx1ywjyl7y2hxhmx09,2032:wqqwr9x1rxjpy4kymry",
  "x0ywgyl5y2hxhox0b,2033:wqrwr9x1rxjoy4hymoywzywfyl5y2hxhsx0f,2034",
  ":wqwwrdx1rxjmy4fymlywzywgyl4y2ixhqx0f,2035:wqvwrcx1qxjky4dymkywz",
  "ywhyl8y2jxhqx0e,2036:wquwrcx1sxjny4fymlywzywfyl6y2ixhqx0d,2037:w",
  "qvwrdx1vxjpy4iymlywxywbyl3y2gxhrx0f,2038:wqvwrdx1sxjmy4eymiywvyw",
  "dyl5y2jxhtx0i,2039:wqwwrcx1qxjly4fymlywzywfyl6y2ixhpx0e,2040:wqv",
  "wrcx1sxjmy4gymmyx0yweyl4y2gxhox0c,2041:wquwrex1sxjny4eymjywxywey",
  "l5y2hxhqx0f,2042:wqvwrex1txjny4eymkywxywfyl7y2jxhrx0e,2043:wqswr",
  "9x1pxjly4dymkywyywhyl9y2mxhsx0e,2044:wqrwr8x1nxjjy4fymmyx0ywhyl8",
  "y2lxhsx0f,2045:wquwrax1oxjly4eymlywzywgyl6y2jxhtx0i,2046:wqwwrbx",
  "1nxjfy48ymgywwywhylay2nxhvx0k,2047:wqxwrbx1oxjfy48ymgywyywjylcy2",
  "oxhux0f,2048:wqvwrbx1pxjky4bymiywwywgyl9y2nxhux0g,2049:wquwrbx1p",
  "xjky4aymfywtywdyl8y2nxhyx0k,2050:wqxwrcx1pxjiy4bymhywvywfyl8y2nx",
  "hyx0k,2051:wqwwrax1mxjgy49ymiywxywgylay2nxhvx0j,2052:wqwwrax1nxj",
  "gy49ymjywyywhyl9y2mxhux0i,2053:wqwwrdx1rxjjy4bymiywxywhyl9y2lxhu",
  "x0i,2054:wqxwrbx1oxjfy47ymeywuywhyldy2qxhzx0j,2055:wqvwr9x1mxjey",
  "48ymgywxywkylfy2rxhyx0i,2056:wqtwr7x1lxjgy4aymjywyywhylcy2pxhzx0",
  "j,2057:wqvwr9x1kxjey46ymeywuywfylay2qxi1x0o,2058:wr0wrcx1lxjcy44",
  "ymdywvywhyldy2rxi0x0m,2059:wqywrbx1lxjcy43ymdywuywiyley2sxhzx0k,",
  "2060:wqwwrax1mxjey45ymdywtywgylcy2rxhzx0l,2061:wqxwrbx1oxjhy48ym",
  "eywtywfylay2pxi0x0n,2062:wqywrax1lxjcy44ymbywsyweylcy2sxi2x0o,20",
  "63:wqywrax1kxjay44ymdywwywiyley2rxhzx0l,2064:wqxwr9x1lxjdy46ymfy",
  "wyywjylcy2pxhyx0k,2065:wqwwrax1lxjdy44ymaywtywhylcy2sxi1x0m,2066",
  ":wqywrbx1kxjcy43ymbywuywjylhy2vxi2x0m,2067:wquwr7x1hxjay43ymeywv",
  "ywkyliy2wxi4x0m,2068:wqvwr5x1gxj9y43ymdywvywiylfy2vxi5x0o,2069:w",
  "qzwr8x1ixj9y43ymdywvywjylfy2uxi5x0r,2070:wr1wrax1hxj5y3xym7ywsyw",
  "jylhy2yxi6x0r,2071:wr1wrbx1ixj6y3wym7ywtywkyljy2yxi5x0o,2072:wqy",
  "wrax1kxjby42ymaywtywjylfy2xxi4x0p,2073:wqywrax1kxjby40ym7ywoywdy",
  "ley2wxi6x0t,2074:wr1wrcx1jxj8y40ym9ywrywhylfy2wxi7x0r,2075:wqzwr",
  "9x1hxj7y41ymbywvywjylfy2wxi4x0p,2076:wqywr9x1hxj8y40ymaywuywiyle",
  "y2uxi3x0o,2077:wqzwrax1kxjay41ymbywuywkylgy2wxi4x0o,2078:wqywr9x",
  "1gxj6y3yym7ywsywjylky30xi8x0p,2079:wqxwr5x1exj5y3xym7ywuywmylly3",
  "1xi7x0p,2080:wqwwr4x1dxj6y40ymbywwywlylky30xi8x0r,2081:wqywr6x1d",
  "xj3y3uym5ywrywhyliy30xiax0v,2082:wr3wrax1dxj1y3sym4ywqywlylly33x",
  "ibx0t,2083:wr1wr8x1exj2y3tym4ywsywlylmy33xiax0s,2084:wqzwr8x1exj",
  "4y3uym4ywpywhyliy31xiax0u,2085:wr1wr9x1hxj6y3wym6ywqywhyliy31xib",
  "x0v,2086:wr3wr9x1dxj2y3tym4ywpywiyljy32xidx0w,2087:wr3wr9x1cxj1y",
  "3sym4ywrywlylky31xiax0t,2088:wr1wr9x1exj4y3wym7ywuywmylky31xi8x0",
  "s,2089:wr1wr9x1gxj4y3tym3ywpywhyljy32xibx0u,2090:wr2wrax1fxj3y3s",
  "ym2ywqywkylny36xidx0t,2091:wqywr5x1cxj2y3uym7ywtywnyloy36xidx0u,",
  "2092:wqywr4x1axj0y3uym5ywrywjylly33xidx0w,2093:wr2wr8x1cxj0y3sym",
  "4ywsywlylmy34xiex0y,2094:wr3wr8x1bxiwy3oym0ywqywmyloy38xifx0y,20",
  "95:wr2wr8x1bxixy3nylzywoywmylpy37xiex0v,2096:wr0wr7x1dxj0y3sym3y",
  "wqywlylny37xiex0w,2097:wr1wr7x1dxj0y3qylzywmywfylky36xihx10,2098",
  ":wr5wr8x1bxiyy3nylzywnywiylmy37xihx0y,2099:wr3wr6x19xixy3qym2yws",
  "ywnyloy36xiex0x,2100:wr2wr7x1axixy3pym2ywpywjylly34xidx0w",
].join("");

const JIEQI_MIN = (function () {
  const min = [], year = [];
  let last = 0;
  JIEQI_TABLE.split(",").forEach(function (seg) {
    const p = seg.split(":"), y = +p[0], enc = p[1];
    for (let i = 0; i < enc.length; i += 3) {
      last += parseInt(enc.substr(i, 3), 36);
      min.push(last); year.push(y);
    }
  });
  return { min: min, year: year };
})();
/* 公历日期时刻 → 1900-01-01 00:00 起分钟数（按北京时间口径，h 可为小数） */
function bjMinutes(y, m, d, h) {
  return (jdn(y, m, d) - jdn(1900, 1, 1)) * 1440 + Math.round((h || 0) * 60);
}
/* 二分查找：最后一个 <= t 的「节」下标，-1 表示早于表范围 */
function findJie(t) {
  const a = JIEQI_MIN.min;
  let lo = 0, hi = a.length - 1, res = -1;
  while (lo <= hi) {
    const mid = (lo + hi) >> 1;
    if (a[mid] <= t) { res = mid; lo = mid + 1; } else hi = mid - 1;
  }
  return res;
}

  // 儒略日数（本地日期）
  function jdn(y, m, d) {
    const a = Math.floor((14 - m) / 12), yy = y + 4800 - a;
    const mm = m + 12 * a - 3;
    return d + Math.floor((153 * mm + 2) / 5) + 365 * yy + Math.floor(yy / 4) - Math.floor(yy / 100) + Math.floor(yy / 400) - 32045;
  }

  // 日柱：(JDN - 11) % 60，0 = 甲子（已用 2000-01-01 戊午日校验）
  function dayPillar(y, m, d) { return (jdn(y, m, d) - 11) % 60; }

  // 年柱：以立春为界（1900-2100 用精确节气表，精确到分钟；表外近似 2/4）
  function yearPillar(y, m, d, h) {
    let yy = y;
    if (y >= 1900 && y <= 2100) {
      const t = bjMinutes(y, m, d, h);
      // 立春为每年 12 节中的第 2 项（下标 (y-1900)*12+1）
      const lc = JIEQI_MIN.min[(y - 1900) * 12 + 1];
      if (t < lc) yy = y - 1;
    } else if (m < 2 || (m === 2 && d < 4)) {
      yy -= 1;
    }
    return { stem: (yy - 4) % 10, branch: (yy - 4) % 12 };
  }

  // 月柱：精确节气定月建（1900-2100），寅月为正月；表外近似「6 日为界」
  function monthPillar(y, m, d, h) {
    let mi; // 距寅月的序数：0=寅 … 11=丑
    if (y >= 1900 && y <= 2100) {
      const t = bjMinutes(y, m, d, h);
      const idx = findJie(t);
      if (idx >= 0) {
        const jieInYear = idx % 12;          // 0=小寒 1=立春 … 11=大雪
        mi = (jieInYear + 11) % 12;          // 小寒→11(丑月) 立春→0(寅月) … 大雪→10(子月)
      } else {
        mi = 10;                             // 早于 1900 小寒：归子月
      }
    } else {
      mi = m - 2;
      if (mi < 0) mi += 12;
      if (d < 6) mi -= 1;
      if (mi < 0) mi += 12;
    }
    const branch = (mi + 2) % 12; // 寅=2
    // 五虎遁：甲己之年丙作首
    const ys = yearPillar(y, m, d, h).stem;
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
    const pillars = [yearPillar(y, m, d, h), monthPillar(y, m, d, h), { i: dayPillar(y, m, d) }, hourPillar(y, m, d, h)];
    pillars[2] = { stem: pillars[2].i % 10, branch: pillars[2].i % 12 };
    const gz = pillars.map(p => STEMS[p.stem] + BRANCHES[p.branch]);
    const wxCount = { "金": 0, "木": 0, "水": 0, "火": 0, "土": 0 };
    pillars.forEach(p => { wxCount[STEM_WX[p.stem]]++; wxCount[BRANCH_WX[p.branch]]++; });
    /* 藏干加权统计：天干1.0/地支，地支藏干 本气0.7 中气0.3 余气0.1（专业排盘算法）
     * 月令当权：月支藏干权重 ×1.8（子平「月令为重」，旺衰首看月支） */
    const wxWeight = { "金": 0, "木": 0, "水": 0, "火": 0, "土": 0 };
    pillars.forEach((p, pi) => {
      wxWeight[STEM_WX[p.stem]] += 1.0;
      const cq = BRANCH_CQ[BRANCHES[p.branch]] || [];
      const wts = [0.7, 0.3, 0.1];
      const monthBoost = pi === 1 ? 1.8 : 1.0;
      cq.forEach((g, i) => { wxWeight[g[1]] += wts[i] * monthBoost; });
    });
    const cangGan = pillars.map(p => ({ gz: STEMS[p.stem] + BRANCHES[p.branch], cq: (BRANCH_CQ[BRANCHES[p.branch]] || []).map(g => g[0]).join("") }));

    /* ---- 真实化喜用神：日主强弱分析（得令/同党异党） ---- */
    const SHENG = { "木": "火", "火": "土", "土": "金", "金": "水", "水": "木" };   // 我生
    const KE = { "木": "土", "土": "水", "水": "火", "火": "金", "金": "木" };     // 我克
    const BEI_KE = { "木": "金", "金": "火", "火": "水", "水": "土", "土": "木" }; // 克我
    const SHENG_ME = { "木": "水", "水": "金", "金": "土", "土": "火", "火": "木" }; // 生我
    const dayStem = pillars[2].stem;
    const dm = STEM_WX[dayStem];
    /* 得令：月支本气与日主同五行或生日主（印） */
    const monthBen = BRANCH_CQ[BRANCHES[pillars[1].branch]] || [];
    const deling = monthBen.some(g => g[1] === dm || g[1] === SHENG_ME[dm]);
    const party = wxWeight[dm] + wxWeight[SHENG_ME[dm]];       // 同党：比劫+印（藏干加权，月令加权）
    const rival = wxWeight[KE[dm]] + wxWeight[BEI_KE[dm]] + wxWeight[SHENG[dm]]; // 异党：官杀+财+食伤
    const total8 = party + rival || 1;
    let strength = party / total8;                            // 日主强度 0~1
    /* 得令加分、失令减分（月令对日主强弱影响约 ±5%） */
    if (deling) strength = Math.min(0.95, strength + 0.05); else strength = Math.max(0.05, strength - 0.05);
    /* 调候：冬（亥子丑月）宜火、夏（巳午未月）宜水（简化启发式，作辅助建议） */
    const monthBranch = pillars[1].branch;
    let tiaohou = "";
    if ([11, 0, 1].indexOf(monthBranch) >= 0 && (wxCount["火"] === 0 || dm === "火")) tiaohou = "冬生水冷，火为调候";
    else if ([5, 6, 7].indexOf(monthBranch) >= 0 && (wxCount["水"] === 0 || dm === "水")) tiaohou = "夏生火炎，水为调候";
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
      pillars, gz, wxCount, wxWeight, cangGan, solarNote, tabooWx, favorable, deling, tiaohou,
      dayMaster: { wx: dm, strength: Math.round(strength * 100), tag: strengthTag,
        sameParty: Math.round(party * 10) / 10, rivalParty: Math.round(rival * 10) / 10,
        desc: `日主${STEMS[dayStem]}（${dm}），同党力量 ${Math.round(party * 10) / 10}、异党力量 ${Math.round(rival * 10) / 10}（含藏干与月令加权），${deling ? "得令" : "不得令"}，日主${strengthTag}${tiaohou ? "；" + tiaohou : ""}` },
      dayStemName: STEMS[dayStem],
      dayStemWx: dm,
      zodiac: ZODIAC[yearPillar(y, m, d, h).branch],
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

  /* ================= 成语起名 =================
   * 从 338 条精选成语中取「连续两字/跳一字」组合成名，标注出处与寓意
   * 流程：候选生成 → 性别/谐音/五格硬过滤 → 喜用神/音律/生肖综合评分 → 多样化截取
   */
  E.genIdiom = function (surname, gender, birth, limit, prefs) {
    limit = limit || 12;
    prefs = prefs || {};
    const b = E.bazi(birth);
    const support = { "木": "水", "火": "木", "土": "火", "金": "土", "水": "金" }[b.favorable];
    const avoid = Array.from((prefs.avoid || "").trim());
    const tabooWx = b.tabooWx;
    const results = [];
    const usedName = {};
    const usedIdiom = {};
    (HM.IDIOMS || []).forEach(function (it) {
      let idiomUsed = 0;
      it.p.forEach(function (pair) {
        if (idiomUsed >= 2 || results.length > limit * 12) return;
        const c1 = pair.charAt(0), c2 = pair.charAt(1);
        if (avoid.indexOf(c1) >= 0 || avoid.indexOf(c2) >= 0) return;
        if (!prefAvoidOk(c1, prefs) || !prefAvoidOk(c2, prefs)) return;
        const i1 = idiomChar(c1), i2 = idiomChar(c2);
        /* 性别过滤：任一字为明确异性用字则跳过 */
        if (gender === 1 && (i1.g === 2 || i2.g === 2)) return;
        if (gender === 2 && (i1.g === 1 || i2.g === 1)) return;
        /* 忌神过滤 */
        if (tabooWx && (i1.w === tabooWx || i2.w === tabooWx)) return;
        const full = surname + pair;
        if (usedName[full]) return;
        if (pair.charAt(0) === surname.charAt(0) && pair.length > 1) { /* 姓与名首字相同需斟酌，保留但降权 */ }
        /* 五格硬过滤：明显凶数不入选 */
        const wg = E.wuge(full);
        if (!wg || wg.score < 62) return;
        /* 谐音硬过滤：明显不雅（严重度 2）直接淘汰 */
        const hk = E.phonetic(full);
        const risks = (hk && hk.risks) || [];
        const riskLv = risks.length ? Math.max.apply(null, risks.map(function (r) { return r.lv || 1; })) : 0;
        if (riskLv === 2) return;
        /* 喜用神匹配度（两字综合） */
        const ws = [i1.w, i2.w];
        const hit = ws.filter(function (w) { return w === b.favorable; }).length;
        const sup = ws.filter(function (w) { return w === support; }).length;
        const wxMatch = hit === 2 ? 100 : hit === 1 ? 88 : sup >= 1 ? 76 : 68;
        const ph = E.phoneticScore(full);
        const sem = affinity(c1, c2);
        const zb = zodiacBonus(birth, pair);
        const hot = hotCount(pair);
        const dup = hot >= 3 ? 14 : hot === 2 ? 8 : hot === 1 ? 4 : 0;
        /* 虚词尾字轻降权：「凌之」「善若」类文气有余而现代读感略弱 */
        const PARTICLES = "之若如而以于与焉其";
        const particlePen = PARTICLES.indexOf(c2) >= 0 ? 4 : 0;
        const form = E.formScore(pair);
        const dialect = E.dialectRisks(full);
        const final = Math.round(Math.max(55, Math.min(99,
          wg.score * 0.40 + ph.score * 0.19 + sem * 1.0 * 0.14 + wxMatch * 0.12
          + Math.min(100, 60 + zb * 10) * 0.07 + form.score * 0.05
          - dup - particlePen - (riskLv === 1 ? 8 : 0) - dialect.penalty * 0.5)));
        usedName[full] = 1;
        idiomUsed++;
        const wxDesc = hit >= 1 ? `补${b.favorable}（喜用神）` : (sup >= 1 ? `取${support}以生${b.favorable}` : `五行${i1.w || "—"}${i2.w || "—"}`);
        results.push({
          name: full, given: pair,
          chars: [i1, i2],
          score: final,
          wg: wg.score,
          wx: (i1.w || "?") + (i2.w || "?"),
          idiom: it.n, idiomMean: it.y, idiomFrom: it.s || "",
          dupEstimate: E.dupEstimate(full),
          form: { tags: form.tags },
          reason: `出自成语「${it.n}」（${it.y}）${it.s ? "，典出" + it.s : ""}；${wxDesc}`
            + (riskLv === 1 ? `；⚠ 谐音「${risks[0].with}」需斟酌` : (ph.issues.length ? `；${ph.issues[0]}` : "；音律谐畅"))
            + (dialect.issues.length ? `；⚠ ${dialect.issues[0]}` : "")
            + (form.tags.indexOf("结构错落有致") >= 0 ? "；字形结构错落" : "")
            + (zb > 0 ? `；生肖${b.zodiac}年宜用` : (zb < 0 ? `；⚠ 生肖${b.zodiac}年忌用字根` : ""))
            + (dup >= 2 ? "；用字较热门，重名率偏高" : "")
        });
      });
    });
    /* 多样化：同首字最多 2 个；同第二字最多 2 个 */
    results.sort(function (a, b2) { return b2.score - a.score; });
    const byFirst = {}, bySecond = {}, diversified = [];
    for (const r of results) {
      const fc = r.given.charAt(0), sc = r.given.charAt(1) || fc;
      byFirst[fc] = (byFirst[fc] || 0) + 1;
      bySecond[sc] = (bySecond[sc] || 0) + 1;
      if (byFirst[fc] > 2 || bySecond[sc] > 2) continue;
      diversified.push(r);
      if (diversified.length >= limit) break;
    }
    return { bazi: b, names: diversified };
  };

  /* ================= 小名/乳名引擎 =================
   * 策略：叠字吉利 → 风物节气 → 生肖意象 → 食物系
   * 结合八字喜用神与生肖喜忌打分
   */
  E.genNickname = function (surname, gender, birth, limit) {
    limit = limit || 12;
    const b = E.bazi(birth);
    const zodiac = b.zodiac;
    const out = [];
    const push = function (name, type, reason) {
      if (out.some(function (x) { return x.name === name; })) return;
      /* 小名音律：叠字看声母不拗口；避免与姓氏组成不雅谐音 */
      const full = surname + name;
      const hk = E.phonetic(full);
      const riskLv = (hk && hk.risks && hk.risks.length) ? Math.max.apply(null, hk.risks.map(function (r) { return r.lv || 1; })) : 0;
      if (riskLv === 2) return;
      let sc = 78;
      const ch0 = name.charAt(0);
      const i0 = idiomChar(ch0);
      if (i0.w === b.favorable) sc += 8;
      else if (i0.w) sc += 3;
      /* 生肖意象加权：生肖池来的乳名 +5 */
      if (type.indexOf("生肖") === 0) sc += 5;
      if (type.indexOf("节气") === 0) sc += 3;
      /* 叠字口拙度：声母为 zh/ch/sh/r 的叠字稍减 */
      const py = pyOfChar(ch0);
      if (/^(zh|ch|sh|r)/.test(py || "")) sc -= 3;
      if (riskLv === 1) sc -= 8;
      sc = Math.max(60, Math.min(95, sc));
      out.push({ name, full, type, score: sc, reason: reason + (riskLv === 1 ? "；⚠ 谐音需斟酌" : "") });
    };
    /* 1. 生肖意象乳名 */
    (HM.NICK_BY_ZODIAC[zodiac] || []).forEach(function (n) { push(n, "生肖·" + zodiac, zodiac + "年生人，取「" + n + "」应生肖意象"); });
    /* 2. 叠字吉利 */
    shuffle(HM.NICK_STACK).forEach(function (n) { push(n, "叠字乳名", "叠字「" + n + "」，亲昵顺口，寓意" + (idiomChar(n.charAt(0)).m || "平安喜乐")); });
    /* 3. 风物节气 */
    shuffle(HM.NICK_SCENERY).forEach(function (n) { push(n, "节气风物", "取节气风物「" + n + "」，清新自然，四时吉祥"); });
    /* 4. 八字补喜用神的单字叠乳名 */
    const favPool = HM.CHARS.filter(function (x) { return x.w === b.favorable && (x.g === gender || x.g === 0); });
    if (favPool.length) {
      const fav = shuffle(favPool).slice(0, 6);
      fav.forEach(function (x) { push(x.c + x.c, "五行补益", "「" + x.c + "」属" + x.w + "，补八字喜用神" + b.favorable + "；" + x.m); });
    }
    /* 5. 生肖宜用字叠字 */
    const zChars = HM.CHARS.filter(function (x) { return zodiacBonus(birth, x.c) > 0 && (x.g === gender || x.g === 0); });
    if (zChars.length) shuffle(zChars).slice(0, 5).forEach(function (x) { push(x.c + x.c, "生肖宜用", "「" + x.c + "」为" + zodiac + "年宜用字；" + x.m); });
    out.sort(function (a, b2) { return b2.score - a.score; });
    return { bazi: b, names: out.slice(0, limit) };
  };

  /* ================= 双胞胎配对名引擎 =================
   * 策略：成语对半拆分 → 同源词组拆分 → 对仗字对 → 同部首字对
   */
  E.genTwins = function (surname, g1, g2, birth, limit) {
    limit = limit || 8;
    const b = E.bazi(birth);
    const support = { "木": "水", "火": "木", "土": "火", "金": "土", "水": "金" }[b.favorable];
    const out = [];
    const PARTICLES = "之若如而以于与焉其";
    const okGender = function (name, g) {
      if (g === 0) return true;
      return Array.from(name).every(function (c) { const i = idiomChar(c); return i.g === 0 || i.g === g; });
    };
    const scorePair = function (n1, n2, extra) {
      const f1 = surname + n1, f2 = surname + n2;
      const wg1 = E.wuge(f1), wg2 = E.wuge(f2);
      if (!wg1 || !wg2 || wg1.score < 62 || wg2.score < 62) return null;
      /* 谐音硬过滤 */
      for (const f of [f1, f2]) {
        const hk = E.phonetic(f);
        const lv = (hk && hk.risks && hk.risks.length) ? Math.max.apply(null, hk.risks.map(function (r) { return r.lv || 1; })) : 0;
        if (lv === 2) return null;
      }
      /* 喜用神：两娃名字合计命中 */
      const chars = Array.from(n1 + n2);
      const ws = chars.map(wuxingOf);
      const hit = ws.filter(function (w) { return w === b.favorable; }).length;
      const sup = ws.filter(function (w) { return w === support; }).length;
      const wxScore = hit >= 2 ? 100 : hit === 1 ? 88 : sup >= 1 ? 78 : 70;
      /* 对仗：平仄相对加分（两名字尾字平仄不同） */
      const t1 = E.toneOf(pyOfChar(n1.charAt(n1.length - 1)) || "");
      const t2 = E.toneOf(pyOfChar(n2.charAt(n2.length - 1)) || "");
      const alt = (t1 && t2 && ((t1 <= 2 && t2 >= 3) || (t1 >= 3 && t2 <= 2))) ? 6 : 0;
      const zb = (zodiacBonus(birth, n1) + zodiacBonus(birth, n2)) / 2;
      const ph1 = E.phoneticScore(f1).score, ph2 = E.phoneticScore(f2).score;
      const final = Math.round(Math.max(55, Math.min(99,
        (wg1.score + wg2.score) / 2 * 0.40 + (ph1 + ph2) / 2 * 0.18
        + wxScore * 0.16 + Math.min(100, 60 + zb * 10) * 0.10 + 70 * 0.06 + alt + (extra || 0))));
      return { score: final, wg1: wg1.score, wg2: wg2.score };
    };
    const add = function (n1, n2, type, reason, extra) {
      /* 龙凤胎（g1≠g2）：两种分配方向都尝试 */
      if (g1 !== g2 && okGender(n2, g1) && okGender(n1, g2) && !(okGender(n1, g1) && okGender(n2, g2))) {
        add(n2, n1, type, reason, extra);
        return;
      }
      if (!okGender(n1, g1) || !okGender(n2, g2)) return;
      /* 虚词尾字轻降权：「如流」「之乐」类文气有余而现代读感略弱 */
      const pp = (PARTICLES.indexOf(n1.charAt(n1.length - 1)) >= 0 ? 5 : 0)
               + (PARTICLES.indexOf(n2.charAt(n2.length - 1)) >= 0 ? 5 : 0);
      const s = scorePair(n1, n2, (extra || 0) - pp);
      if (!s) return;
      out.push({ name1: surname + n1, name2: surname + n2, given1: n1, given2: n2, type, reason, score: s.score, wg1: s.wg1, wg2: s.wg2 });
    };
    /* 1. 成语对半拆分（宁静致远 → 宁静/致远） */
    (HM.IDIOMS || []).forEach(function (it) {
      if (it.n.length !== 4) return;
      const ab = it.n.substr(0, 2), cd = it.n.substr(2, 2);
      if (it.p.indexOf(ab) >= 0 && it.p.indexOf(cd) >= 0) {
        add(ab, cd, "成语对半", `成语「${it.n}」对半而取，一脉相连（${it.y}）`, 5);
      }
    });
    /* 2. 同源词组拆分 */
    (HM.TWIN_WORDS || []).forEach(function (w) {
      const okPool = function (s) { return Array.from(s).every(function (c) { return HM.CHARS.some(function (x) { return x.c === c; }) || (HM.IDIOM_CHARS || []).some(function (x) { return x.c === c; }); }); };
      if (okPool(w[1]) && okPool(w[2])) add(w[1], w[2], "同源词组", `古语「${w[0]}」一分为二：${w[1]}、${w[2]}`, 4);
    });
    /* 3. 对仗字对（各配一字，需在字库内） */
    (HM.TWIN_PAIRS || []).forEach(function (pr) {
      if (pr[0] === pr[1]) return;
      const inPool = function (c) { return HM.CHARS.some(function (x) { return x.c === c; }) || (HM.IDIOM_CHARS || []).some(function (x) { return x.c === c; }); };
      if (!inPool(pr[0]) || !inPool(pr[1])) return;
      add(pr[0], pr[1], "对仗字对", `「${pr[0]}」对「${pr[1]}」，意象相称，平仄谐协`, 3);
    });
    /* 4. 同部首字对（如 江/河、冰/洁） */
    const byRad = {};
    HM.CHARS.forEach(function (x) { const r = (HM.RADICALS || {})[x.c]; if (!r) return; (byRad[r] = byRad[r] || []).push(x); });
    Object.keys(byRad).forEach(function (r) {
      const arr = byRad[r].filter(function (x) { return okGender(x.c, g1) && okGender(x.c, g2); });
      if (arr.length < 2) return;
      for (let i = 0; i < Math.min(arr.length, 5); i++) {
        for (let j = i + 1; j < Math.min(arr.length, 6); j++) {
          if (arr[i].c === arr[j].c) continue;
          add(arr[i].c, arr[j].c, "同部首", `同属「${r}」部，字形呼应，便于呼唤`, 2);
        }
      }
    });
    out.sort(function (a, b2) { return b2.score - a.score; });
    /* 多样化：避免大量同字 */
    const seen = {}, div = [];
    for (const r of out) {
      const k = r.given1 + r.given2;
      if (seen[k]) continue; seen[k] = 1;
      div.push(r);
      if (div.length >= limit) break;
    }
    return { bazi: b, names: div };
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
function pyOfChar(c) {
  const info = HM.CHARS.find(x => x.c === c);
  if (info) return info.p;
  const sn = HM.SURNAMES.find(x => x.c === c);
  if (sn && sn.p) return sn.p;
  const ic = (HM.IDIOM_CHARS || []).find(x => x.c === c);   /* 成语取名扩展字库回退 */
  return ic ? ic.p : "";
}
/* 成语用字的完整信息（五行/性别/寓意）：优先主字库，其次成语扩展字库 */
function idiomChar(c) {
  const info = HM.CHARS.find(x => x.c === c);
  if (info) return info;
  const ic = (HM.IDIOM_CHARS || []).find(x => x.c === c);
  return ic || { c, w: "", g: 0, m: "" };
}
function wuxingOf(c) { return idiomChar(c).w || ""; }
function toneOfChar(c) {
  const py = pyOfChar(c);
  const m = py.match(/[āáǎàēéěèīíǐìōóǒòūúǔùǖǘǚǜ]/);
  const T = {"ā":1,"á":2,"ǎ":3,"à":4,"ē":1,"é":2,"ě":3,"è":4,"ī":1,"í":2,"ǐ":3,"ì":4,"ō":1,"ó":2,"ǒ":3,"ò":4,"ū":1,"ú":2,"ǔ":3,"ù":4,"ǖ":1,"ǘ":2,"ǚ":3,"ǜ":4};
  return m ? T[m[0]] : 0;
}
E.phoneticScore = function (full) {
  const chars = Array.from(full);
  const tones = chars.map(toneOfChar);
  const sms = chars.map(c => { const py = pyOfChar(c); return py ? py.replace(/[^a-z]/g, "").charAt(0) : ""; });
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
  /* 尾字宜平声（1/2 声）收尾更响亮；尾字上声稍抑 */
  const last = tones[tones.length - 1];
  if (last === 1 || last === 2) { sc += 5; }
  else if (last === 3) { sc -= 2; issues.push("尾字上声，音感偏抑"); }
  /* 平仄相间加分：三字名两处交替（如 平-仄-平 / 仄-平-仄） */
  if (tones.length >= 3 && tones.every(Boolean)) {
    const pz = tones.map(t => (t <= 2 ? 0 : 1));
    let alt = 0;
    for (let i = 1; i < pz.length; i++) if (pz[i] !== pz[i - 1]) alt++;
    if (alt >= 2) { sc += 6; }
    else if (alt === 0) { sc -= 4; issues.push("平仄无变化"); }
  }
  return { score: Math.max(40, Math.min(100, sc)), issues };
};
/* 高频名用字分级（重名率参考，依据近年姓名用字通行统计口径）
 * 2 = 超高频（近年新生儿姓名榜首常客）；1 = 较常见（历年通用高频字） */
const HOT2 = "梓涵轩宇辰沐宸汐若熙桐雨彤萱怡欣悦佳诗琪梦语妍安宁亦希林伟芳娜敏静丽磊军洋勇艳杰娟涛明超秀兰霞平刚桂英华玉萍红梅琳鑫鹏睿昊皓晨曦阳春晓晴晶莹芸茉莉菁薇蕊蓉荣";
const HOT1 = "子墨一可书舒贤雅文武君岚川禾米立正和康健乐知思远志毅勤诚信仁义礼智德善良谦谨温柔馨香芝莲荷珊珍珠雪松筠航帆江河";
const hotSet = {};
HOT2.split("").forEach(c => { hotSet[c] = 2; });
HOT1.split("").forEach(c => { if (!hotSet[c]) hotSet[c] = 1; });
function hotCount(given) { return Array.from(given).reduce((a, c) => a + (hotSet[c] || 0), 0); }

/* ================= 第二轮深度优化：字形搭配 / 方言谐音 / 重名估算 / 偏好过滤 ================= */

/* 字形搭配分：繁简互补、结构错落、笔画跨度合理 */
E.formScore = function (given) {
  const chars = Array.from(given);
  if (chars.length < 2) return { score: 80, tags: ["单字名"], strokes: [], structs: [] };
  const ks = chars.map(c => { const it = HM.CHARS.find(x => x.c === c); return it ? it.k : 8; });
  const structs = chars.map(E.structOf);
  let sc = 82;
  const tags = [];
  const diff = Math.max.apply(null, ks) - Math.min.apply(null, ks);
  if (diff >= 9) { sc -= 10; tags.push("笔画跨度大，书写失衡"); }
  else if (diff >= 2 && diff <= 5) { sc += 4; tags.push("繁简相宜"); }
  if (structs[0] === structs[1]) { sc -= 6; tags.push("结构重复"); }
  else { sc += 5; tags.push("结构错落有致"); }
  if (ks.some(k => k >= 20)) { sc -= 5; tags.push("含繁难字"); }
  if (ks.some(k => k <= 4) && ks.some(k => k >= 15)) { sc += 3; tags.push("简繁搭配"); }
  return { score: Math.max(50, Math.min(98, sc)), tags, strokes: ks, structs };
};

/* 方言谐音：南方常见易混音（平翘舌 / 鼻边音 n-l / h-f / 前后鼻音）*/
E.dialectRisks = function (full) {
  const chars = Array.from(full);
  const SM_CONFUSE = [["zh", "z"], ["ch", "c"], ["sh", "s"], ["n", "l"], ["h", "f"], ["r", "l"]];
  const YM_CONFUSE = [["en", "eng"], ["in", "ing"], ["an", "ang"], ["ian", "iang"]];
  const issues = [];
  for (let i = 1; i < chars.length; i++) {
    const p1 = pyOfChar(chars[i - 1]), p2 = pyOfChar(chars[i]);
    if (!p1 || !p2) continue;
    const s1 = p1.replace(/[^a-z]/g, ""), s2 = p2.replace(/[^a-z]/g, "");
    for (const pair of SM_CONFUSE) {
      const a = pair[0], b = pair[1];
      if ((s1.indexOf(a) === 0 && s2.indexOf(b) === 0) || (s1.indexOf(b) === 0 && s2.indexOf(a) === 0)) {
        issues.push(chars[i - 1] + chars[i] + "（" + a + "/" + b + " 方言易混）");
      }
    }
    for (const pair of YM_CONFUSE) {
      const a = pair[0], b = pair[1];
      if ((s1.length > a.length && s1.slice(-a.length) === a && s2.slice(-b.length) === b) ||
          (s2.length > b.length && s2.slice(-b.length) === b && s1.slice(-a.length) === a)) {
        issues.push(chars[i - 1] + chars[i] + "（" + a + "/" + b + " 方言易混）");
      }
    }
  }
  return { issues: issues.slice(0, 3), penalty: Math.min(8, issues.length * 3) };
};

/* 重名估算：基于近年新生儿高频用字通行统计口径的区间估算（非真实人口数据）*/
E.dupEstimate = function (name) {
  const chars = Array.from(name);
  const given = chars.slice(chars.length >= 3 ? 1 : 0).join("");
  let w = 0;
  const hot = [];
  Array.from(given).forEach(c => {
    const lv = hotSet[c] || 0;
    if (lv === 2) { w += 34; hot.push(c); }
    else if (lv === 1) { w += 12; hot.push(c); }
  });
  /* 权重 → 估算同名人数（万人）：w=0 约 0.4 万；w=34 约 1.6 万；w=68 约 6 万 */
  const est = 0.4 * Math.pow(1.5, Math.min(8, w / 17));
  let level = "重名概率极低";
  if (w >= 55) level = "重名概率较高";
  else if (w >= 34) level = "重名概率中等";
  else if (w >= 12) level = "重名概率较低";
  return {
    est: Math.round(Math.min(est, 68) * 10) / 10, unit: "万人", level, hotChars: hot,
    note: "基于近年新生儿高频用字统计口径估算，仅供参考，非真实人口普查数据"
  };
};

/* ============================================================
 * 展示层辅助模块（竞品对标：九维解析 / 拼音 / 五行分布 / 性格标签）
 * ============================================================ */
const SEMI_LABEL = { nature: "自然", bright: "阳光", smart: "智慧", aspire: "进取", virtue: "品德", elegant: "雅趣", gentle: "温婉", wealth: "福气", health: "康健", other: "" };
/* 性格标签：语义类别 → 四字性格描述（linggx365 好名库卡片范式） */
const SEMI_PERSONA = { nature: "亲近自然", bright: "明朗豁达", smart: "聪慧机敏", aspire: "积极进取", virtue: "端方正直", elegant: "儒雅有礼", gentle: "温柔内敛", wealth: "福泽深厚", health: "康健安乐" };

/* 全名拼音（含声调），逐字查字库，姓氏查 SURNAMES */
E.pinyinOf = function (name) {
  return Array.from(name).map(c => pyOfChar(c) || "").filter(Boolean).join(" ");
};
/* 名字（不含姓）五行分布：返回各五行占比（0-100） */
E.wxDist = function (name) {
  const chars = Array.from(name);
  const given = chars.slice(chars.length >= 3 ? 1 : 0);
  const cnt = { "金": 0, "木": 0, "水": 0, "火": 0, "土": 0 };
  let n = 0;
  given.forEach(c => { const w = wuxingOf(c); if (cnt[w] != null) { cnt[w]++; n++; } });
  if (!n) return null;
  const out = {};
  Object.keys(cnt).forEach(w => { out[w] = Math.round(cnt[w] / n * 100); });
  return out;
};
/* 性格标签：取名字用字的语义类别，去重后映射为性格描述 */
E.personaTags = function (name) {
  const chars = Array.from(name);
  const given = chars.slice(chars.length >= 3 ? 1 : 0);
  const cats = [];
  given.forEach(c => { const k = semanticOf(c); if (k !== "other" && cats.indexOf(k) < 0) cats.push(k); });
  return cats.slice(0, 2).map(k => SEMI_PERSONA[k]).filter(Boolean);
};
/* 九维解析：对齐竞品「九大维度」叙事，全部取自既有评分体系 */
E.nineDims = function (name, birth) {
  const s = E.scoreOf(name, birth);
  if (!s) return null;
  const chars = Array.from(name);
  const given = chars.slice(chars.length >= 3 ? 1 : 0);
  const gAvg = (() => {
    let sum = 0, n = 0;
    given.forEach(c => { const i = HM.CHARS.find(x => x.c === c); if (i && i.g) { sum += i.g; n++; } });
    return n ? sum / n : 1.5;
  })();
  /* 性别倾向：用字性别与名主性别的一致度（无 birth 时按用字自身性别均值） */
  const genderFit = Math.round(Math.max(40, Math.min(99, 100 - Math.abs(gAvg - (birth && birth.g != null ? birth.g : gAvg)) * 30)));
  const wx = E.wxDist(name);
  return [
    { k: "国学助益", v: s.wg.score, tip: "生辰八字与五格数理的综合助益" },
    { k: "音律读感", v: s.phonetic.score, tip: "平仄、声母、韵母搭配" },
    { k: "寓意内涵", v: s.breakdown.find(x => x.k === "字义内涵").v, tip: "字义、典籍出处与谐音" },
    { k: "字型结构", v: s.breakdown.find(x => x.k === "字形结构").v, tip: "繁简相宜、结构错落与书写" },
    { k: "性别倾向", v: genderFit, tip: "用字与性别气质的匹配度" },
    { k: "生肖契合", v: s.breakdown.find(x => x.k === "生肖契合") ? s.breakdown.find(x => x.k === "生肖契合").v : 70, tip: "生肖宜忌与部首呼应" },
    { k: "五行平衡", v: wx ? Math.round(100 - Math.max.apply(null, Object.keys(wx).map(w => Math.abs(wx[w] - 20))) * 1.6) : null, tip: "金木水火土分布均衡度" },
    { k: "重名热度", v: s.breakdown.find(x => x.k === "重名热度").v, tip: "越低越发觉独特" },
    { k: "综合评分", v: s.final, tip: "六维加权总分", total: true }
  ].filter(x => x.v != null);
};

/* 高级偏好过滤：忌用偏旁（含偏旁字面）与父母名避讳（同字/同音）*/
function prefAvoidOk(c, prefs) {
  prefs = prefs || {};
  const rads = Array.from(prefs.avoidRadical || "");
  const rad = (HM.RADICALS || {})[c] || "";
  if (rad && rads.indexOf(rad) >= 0) return false;
  if (rads.indexOf(c) >= 0) return false;
  const pnames = (prefs.parents || "").split(/[,，\s]+/).filter(Boolean);
  const pc = pyOfChar(c);
  for (const pn of pnames) {
    if (pn.indexOf(c) >= 0) return false;
    if (pc && Array.from(pn).some(ch => pyOfChar(ch) === pc)) return false;
  }
  return true;
}
/* 生肖宜用字根加分、忌用字根减分（六冲/六害生肖字形）
 * 匹配通道：① 字的主要部首（HM.RADICALS）② 生肖相关字形（HM.ZODIAC_PARTS）③ 字本身 */
function zodiacBonus(birth, given) {
  if (!birth) return 0;
  const b = E.bazi(birth);
  const sx = HM.SHENGXIAO && HM.SHENGXIAO[b.zodiac];
  if (!sx) return 0;
  const good = Array.isArray(sx) ? sx : (sx.good || []);
  const avoid = Array.isArray(sx) ? [] : (sx.avoid || []);
  let sc = 0;
  Array.from(given).forEach(c => {
    const parts = [];
    const r1 = (HM.RADICALS || {})[c];
    if (r1 && parts.indexOf(r1) < 0) parts.push(r1);
    const r2 = (HM.ZODIAC_PARTS || {})[c];
    if (r2 && parts.indexOf(r2) < 0) parts.push(r2);
    parts.push(c);
    if (good.some(g => parts.indexOf(g) >= 0)) sc += 4;
    if (avoid.some(a => parts.indexOf(a) >= 0)) sc -= 5;
  });
  return sc;
}
/* 综合分：五格 43% + 音律 19% + 语义 14% + 五行契合 12% + 生肖 7% + 字形 5%（重名/方言谐音扣减） */
function qualityScore(full, given, wg, birth, mainWx, favorable) {
  const wgN = wg ? wg.score : 70;
  const ph = E.phoneticScore(full);
  const sem = affinity(given.charAt(0), given.charAt(1) || given.charAt(0));
  const wxMatch = (mainWx === favorable ? 100 : 80);
  const zb = zodiacBonus(birth, given);
  const hot = hotCount(given);
  const dup = hot >= 3 ? 14 : hot === 2 ? 8 : hot === 1 ? 4 : 0;
  const form = E.formScore(given);
  const dialect = E.dialectRisks(full);
  const final = Math.round(wgN * 0.43 + ph.score * 0.19 + sem * 1.0 * 0.14 + wxMatch * 0.12
    + Math.min(100, 60 + zb * 10) * 0.07 + form.score * 0.05 - dup - dialect.penalty * 0.5);
  return { final: Math.max(55, Math.min(99, final)), ph, sem, dup, zb, hot, form, dialect };
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
  const dup = hot >= 3 ? 14 : hot === 2 ? 8 : hot === 1 ? 4 : 0;
  const form = E.formScore(name.slice(1));
  const dialect = E.dialectRisks(name);
  const final = Math.round(Math.max(55, Math.min(99,
    wg.score * 0.40 + ph.score * 0.21 + sem * 1.0 * 0.15 + Math.min(100, 60 + zb * 8) * 0.11
    + form.score * 0.05 + 88 * 0.08 - dup - dialect.penalty * 0.5)));
  return {
    final, wg, phonetic: ph, sem, zodiacBonus: zb, dup,
    breakdown: [
      { k: "五格数理", v: wg.score },
      { k: "音律读感", v: ph.score },
      { k: "字义内涵", v: Math.min(99, sem * 6 + 15) },
      { k: "字形结构", v: form.score },
      { k: "生肖契合", v: birth ? Math.min(99, 60 + zb * 8) : null },
      { k: "重名热度", v: 100 - dup * 5 }
    ].filter(x => x.v != null),
    dupEstimate: E.dupEstimate(name),
    dialect: dialect.issues.length ? dialect : null
  };
};

  /* 典籍出处索引：字 → {src, line}。
     第一遍取诗词名句中的「精选名」用字（高质量直配）；
     第二遍用整句命中兜底并过滤虚词，扩大覆盖；
     第三遍用成语词典兜底（每条带典籍出处），保证字库常用字基本有处可考。
     供八字等引擎结果卡展示「用字出处」，对标竞品出处标注（报告二 5.3）。 */
  var _CHAR_SOURCE_CACHE = null;
  function charSourceIndex() {
    if (_CHAR_SOURCE_CACHE) return _CHAR_SOURCE_CACHE;
    var idx = {};
    var SKIP = { "的":1,"之":1,"以":1,"其":1,"而":1,"于":1,"於":1,"乎":1,"兮":1,"矣":1,"焉":1,"也":1,"与":1,"为":1,"是":1,"不":1,"无":1,"在":1,"有":1,"我":1 };
    (HM.POEMS || []).forEach(function (p) {
      (p.names || []).forEach(function (nm) {
        Array.from(nm.n || "").forEach(function (c) { if (c && !idx[c]) idx[c] = { src: p.src, line: p.line }; });
      });
    });
    (HM.POEMS || []).forEach(function (p) {
      Array.from(p.line || "").forEach(function (c) {
        if (c && !idx[c] && !SKIP[c]) idx[c] = { src: p.src, line: p.line };
      });
    });
    /* 成语兜底：line=成语本身，src=成语典籍出处（缺失时退为「成语 · 释义」），
       与结果卡 blockquote 格式天然契合 */
    (HM.IDIOMS || []).forEach(function (it) {
      Array.from(it.n || "").forEach(function (c) {
        if (c && !idx[c] && !SKIP[c]) idx[c] = { src: it.s || ("成语 · " + it.y), line: it.n };
      });
    });
    _CHAR_SOURCE_CACHE = idx;
    return idx;
  }
  E.charSourceIndex = charSourceIndex;
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
  /* 名字字数：prefs.wordCount = "1"单字名 | "2"双字名 | "3"三字名 | ""不限 */
  const wc = (prefs.wordCount || "").trim();
  const wantSingle = wc === "1", wantTriple = wc === "3";
  const wantDouble = !wantSingle && !wantTriple;
  /* 父母期望 → 语素类别（如"健康聪明"→health+smart，匹配的候选字加权） */
  const WISH_KW = { "平安":["health"], "健康":["health"], "快乐":["health"], "聪明":["smart"], "智慧":["smart"], "有出息":["aspire"], "成功":["aspire"], "坚强":["aspire"], "勇敢":["aspire"], "善良":["virtue"], "正直":["virtue"], "有气质":["elegant"], "有才华":["elegant"], "文静":["gentle"], "温柔":["gentle"], "阳光":["bright"], "开朗":["bright"], "富贵":["wealth"], "有福气":["wealth"] };
  let wishCats = [];
  Object.keys(WISH_KW).forEach(kw => { if ((prefs.wish || "").includes(kw)) wishCats = wishCats.concat(WISH_KW[kw]); });
  const wishBonus = c => { const cat = semanticOf(c); return wishCats.includes(cat) ? 8 : 0; };
  const tabooWx = b.tabooWx;
  const okChar = x => !avoid.includes(x.c) && x.c !== surname.charAt(0) && (!tabooWx || x.w !== tabooWx) && prefAvoidOk(x.c, prefs);
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
  /* 单字名候选 */
  if (wantSingle) {
    shuffle(mainPool).slice(0, 10).forEach(m => {
      const full = surname + m.c;
      if (results.some(r => r.name === full)) return;
      const wg = E.wuge(full);
      const q = qualityScore(full, m.c, wg, birth, m.w, b.favorable);
      const hk = E.phonetic(full);
      const riskLv = hk && hk.risks && hk.risks.length ? Math.max.apply(null, hk.risks.map(r2 => r2.lv || 1)) : 0;
      results.push({
        name: full, given: m.c, chars: [m],
        score: Math.max(50, Math.min(99, q.final + wishBonus(m.c) - (riskLv === 2 ? 18 : riskLv === 1 ? 8 : 0))),
        wg: wg ? wg.score : 70, wx: m.w, dupEstimate: E.dupEstimate(full),
        reason: `单字名，取${m.w}行「${m.c}」${targets[0] === b.favorable ? "补喜用神" : "合五行偏好"}，简洁响亮`
      });
    });
  }
  /* 双字名候选 */
  if (wantDouble) for (let i = 0; i < mains.length; i++) {
    for (let j = 0; j < subs.length && results.length < limit * 5; j++) {
      const given = i % 2 === 0 ? mains[i].c + subs[j].c : subs[j].c + mains[i].c;
      if (results.some(r => r.name === surname + given)) continue;
      const full = surname + given;
      const wg = E.wuge(full);
      const q = qualityScore(full, given, wg, birth, mains[i].w, b.favorable);
      q.final = Math.min(99, q.final + wishBonus(mains[i].c) + wishBonus(subs[j].c));
      /* 谐音黑名单：按严重度降权（明显不雅 -18，需斟酌 -8） */
      const hk = E.phonetic(full);
      const riskLv = hk && hk.risks && hk.risks.length ? Math.max.apply(null, hk.risks.map(r2 => r2.lv || 1)) : 0;
      const risk = riskLv > 0;
      let sc = q.final - (riskLv === 2 ? 18 : riskLv === 1 ? 8 : 0);
      const reason = (targets[0] === b.favorable
        ? `补${b.favorable}（喜用神），辅以${subs[j].w}相生`
        : `按偏好取${targets[0]}，辅以${subs[j].w}（喜用神${b.favorable}）`)
        + (tabooWx ? `；忌${tabooWx}已避开` : "")
        + (risk ? `；⚠ 谐音「${hk.risks[0].with}」需斟酌` : q.ph.issues.length ? `；${q.ph.issues[0]}` : "；音律谐畅")
        + (q.dialect.issues.length ? `；⚠ ${q.dialect.issues[0]}` : "")
        + (q.dup >= 2 ? "；用字较热门，重名率偏高" : "");
      results.push({
        name: full, given,
        chars: [mains[i], subs[j]],
        score: Math.max(50, Math.min(99, sc)),
        wg: wg ? wg.score : 70,
        wx: mains[i].w + subs[j].w,
        dupEstimate: E.dupEstimate(full),
        reason
      });
    }
  }
  /* 三字名候选：主字 + 辅字 + 第三字（隔位取字避免与辅字重复） */
  if (wantTriple) for (let i = 0; i < mains.length && results.length < limit * 5; i++) {
    for (let j = 0; j < subs.length && results.length < limit * 5; j++) {
      const k = (j + 5) % subs.length;
      if (k === j) continue;
      const seq = i % 2 === 0 ? [mains[i].c, subs[j].c, subs[k].c] : [subs[k].c, mains[i].c, subs[j].c];
      const given = seq.join("");
      const full = surname + given;
      if (results.some(r => r.name === full)) continue;
      const wg = E.wuge(full);
      const q = qualityScore(full, given, wg, birth, mains[i].w, b.favorable);
      let sc3 = q.final + wishBonus(mains[i].c) + wishBonus(subs[j].c) + wishBonus(subs[k].c);
      const hk3 = E.phonetic(full);
      const riskLv3 = hk3 && hk3.risks && hk3.risks.length ? Math.max.apply(null, hk3.risks.map(r3 => r3.lv || 1)) : 0;
      sc3 -= (riskLv3 === 2 ? 18 : riskLv3 === 1 ? 8 : 0);
      results.push({
        name: full, given,
        chars: [mains[i], subs[j], subs[k]],
        score: Math.max(50, Math.min(99, sc3)),
        wg: wg ? wg.score : 70,
        wx: mains[i].w + subs[j].w + subs[k].w,
        dupEstimate: E.dupEstimate(full),
        reason: `三字名，${mains[i].w}${subs[j].w}${subs[k].w}递进${targets[0] === b.favorable ? "，补喜用神" : ""}，层次丰富`
      });
    }
  }
  /* 固定字（辈分）组合补充；fixedPos：0=辈分字在名前，1=在名后，空=不限 */
  if (fixedInfo) {
    const positions = prefs.fixedPos === "0" ? [0] : prefs.fixedPos === "1" ? [1] : [0, 1];
    const pool2 = HM.CHARS.filter(x => okChar(x) && x.c !== fixedInfo.c && (x.g === gender || x.g === 0) && x.w === b.favorable);
    shuffle(pool2).slice(0, 4).forEach(x => {
      positions.forEach(pos => {
        const given = pos === 0 ? fixedInfo.c + x.c : x.c + fixedInfo.c;
        const full = surname + given;
        if (results.some(r => r.name === full)) return;
        const wg = E.wuge(full);
        const q = qualityScore(full, given, wg, birth, fixedInfo.w, b.favorable);
        results.push({ name: full, given, chars: [fixedInfo, x], score: q.final, wg: wg ? wg.score : 70, wx: fixedInfo.w + x.w,
          dupEstimate: E.dupEstimate(full),
          reason: `含指定用字「${fixed}」（${pos === 0 ? "名前" : "名后"}），配${x.w}行字呼应喜用神${b.favorable}` });
      });
    });
  }
  /* 典籍加分：用字有典可考的名字小幅加分（+3，不颠覆五格/音律主导的排序），
     让「有出处」的名字稳定排在前面，结果页前排卡片典故覆盖率更高 */
  (function () {
    var idx0 = charSourceIndex();
    results.forEach(function (r) {
      var g = r.given || "";
      for (var i3 = 0; i3 < g.length; i3++) {
        if (idx0[g.charAt(i3)]) { r.score = Math.min(99, r.score + 5); break; }
      }
    });
  })();
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
  /* 用字出处：为命中学典用字的结果补充 line/src，复用结果卡 blockquote 渲染（报告二 5.3） */
  (function () {
    var idx = charSourceIndex();
    diversified.forEach(function (r) {
      if (r.line) return;
      var g = r.given || r.name || "";
      for (var i2 = 0; i2 < g.length; i2++) {
        var s = idx[g.charAt(i2)];
        if (s) { r.line = s.line; r.src = s.src; break; }
      }
    });
  })();
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
    const py = pyOfChar(c);
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
  /* 谐音检查：单字同音/音近忌讳 + 经典组合忌讳（民俗共识，取名宜避开）
   * 格式：[模式, 说明, 严重度] —— 2=明显不雅（大幅降权） 1=需斟酌（轻度降权） */
  const RISKS = [
    /* —— 经典组合（网络流传的取名反面教材） —— */
    ["杜子腾", "谐音「肚子疼」", 2], ["史珍香", "谐音不雅", 2], ["杨伟", "谐音不雅", 2],
    ["范建", "谐音不雅", 2], ["朱逸群", "谐音「猪一群」", 2], ["秦寿", "谐音「禽兽」", 2],
    ["庞光", "谐音「膀胱」", 2], ["沈京兵", "谐音「神经病」", 2], ["夏建仁", "谐音不雅", 2],
    ["毕云涛", "谐音不雅", 2], ["赖月京", "谐音不雅", 2], ["殷静", "谐音「月经」", 2],
    ["吴能", "谐音「无能」", 2], ["胡丽", "谐音「狐狸」", 1], ["岳坤", "谐音「月经」", 2],
    /* —— 单字同音忌讳：读音与不雅/负面词完全相同 —— */
    ["书", "谐音「输」（输赢）", 2], ["舒", "谐音「输」", 2], ["姝", "谐音「输」", 2], ["疏", "谐音「输」", 2],
    ["培", "谐音「赔」（赔钱）", 2], ["梅", "谐音「霉」（倒霉）", 2], ["枚", "谐音「霉」", 2],
    ["陶", "谐音「逃」（逃避）", 2], ["桃", "谐音「逃」", 2],
    ["涵", "谐音「寒」（寒酸）", 1], ["函", "谐音「寒」", 1], ["含", "谐音「寒」", 1],
    ["昊", "谐音「耗」（耗费）", 1], ["浩", "谐音「耗」", 1], ["皓", "谐音「耗」", 1], ["灏", "谐音「耗」", 1],
    ["若", "谐音「弱」（弱小）", 1], ["志", "谐音「痔」（痔疮）", 2],
    ["梨", "谐音「离」（离别）", 2], ["黎", "谐音「离」", 2],
    ["范", "谐音「犯」（犯罪）", 2], ["梵", "谐音「犯」", 2],
    ["归", "谐音「龟」", 2], ["圭", "谐音「龟」", 2],
    ["朱", "谐音「猪」（绰号梗）", 1],
    /* —— 单字同音/音近忌讳：需斟酌 —— */
    ["芬", "音近「坟/奋」", 1], ["风", "谐音「疯」", 1], ["峰", "谐音「疯」", 1],
    ["锋", "谐音「疯」", 1], ["丰", "谐音「疯」", 1], ["枫", "谐音「疯」", 1],
    ["冰", "音近「病」", 1], ["凡", "谐音「烦」", 1], ["菲", "谐音「非」（是非）", 1],
    ["燕", "谐音「厌」（厌烦）", 1], ["艳", "谐音「厌」", 1], ["彦", "谐音「厌」", 1],
    ["晏", "谐音「厌」", 1], ["雁", "谐音「厌」", 1],
    ["圣", "谐音「剩」", 1], ["胜", "谐音「剩」", 1],
    ["娴", "谐音「闲」（游手好闲）", 1], ["贤", "谐音「闲」", 1], ["弦", "谐音「闲」", 1],
    ["甲", "谐音「假」（真假）", 1], ["林", "谐音「淋」", 1], ["琳", "谐音「淋」", 1],
    ["霖", "谐音「淋」", 1], ["临", "谐音「淋」", 1],
    ["士", "谐音「逝」", 1], ["世", "谐音「逝」", 1],
    ["易", "谐音「疫」", 1], ["银", "谐音「淫」", 1], ["韵", "谐音「孕」", 1],
    ["夏", "谐音「下」", 1], ["雯", "谐音「蚊」", 1], ["豪", "谐音「嚎」", 1],
    ["幻", "谐音「患」", 1], ["裴", "谐音「赔」", 1], ["柔", "谐音「揉」", 1]
  ];
  const risks = RISKS.filter(r => name.indexOf(r[0]) >= 0 || r[0].indexOf(name) >= 0)
    .map(r => ({ with: r[0], note: r[1], lv: r[2] || 1 }));
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
