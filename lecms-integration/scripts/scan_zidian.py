#!/usr/bin/env python3.11
"""扫描 zidian/ 静态单字页，提取可扩充进起名字库的候选字。
输出: scan_result.json（候选字 + 全量统计 + 现有167字校验报告）
字段: c(字) p(拼音) radical(部首) k(总笔画≈康熙,仅收简繁同形字) g(性别) gloss(释义首句)
五行不在本步判定（后续按部首规则映射并用现有库校准）。
"""
import os, re, json, sys

ZD = "/var/www/lecms/zidian"
OUT = "/workspace/lecms-integration/scripts/scan_result.json"

def parse_page(path):
    try:
        s = open(path, encoding="utf-8").read()
    except Exception:
        return None
    body = re.sub(r"<script.*?</script>", "", s, flags=re.S)
    body = re.sub(r"<[^>]+>", "|", body)
    body = re.sub(r"\|+", "|", body)

    def grab(pat):
        m = re.search(pat, body)
        return m.group(1).strip() if m else ""

    py = grab(r"\|拼音\|([^|]+)\|")
    strokes = grab(r"\|总笔画\|(\d+)画\|")
    radical = grab(r"\|(\w{1,2}部)\|") or grab(r"·\|?\|?([^|]{1,2}部)\|")
    xingming = grab(r"\|姓名学\|([^|]+)\|")
    fanjian = grab(r"\|繁简对应\|([^|]+)\|")
    gloss = ""
    gm = re.search(r"\|基本解释\|[^(|]{1,6}\|?\(?[^|]*\|?\(1\)(\([^)]*\))?([^|]{4,60})", body)
    if gm:
        gloss = gm.group(2).strip()
    return {"p": py, "k": strokes, "radical": radical, "xingming": xingming,
            "fanjian": fanjian, "gloss": gloss[:40]}

def main():
    files = [f for f in os.listdir(ZD) if f.endswith(".html") and not f.startswith("bs-")]
    stats = {"total": len(files), "kept": 0, "reject": {}}
    kept, seen = [], set()

    def reject(reason):
        stats["reject"][reason] = stats["reject"].get(reason, 0) + 1

    for fn in files:
        ch = fn[:-5]
        if len(ch) != 1 or not re.match(r"[\u4e00-\u9fff]", ch):
            reject("非基本区单字"); continue
        if ch in seen:
            reject("文件重名"); continue
        seen.add(ch)
        d = parse_page(os.path.join(ZD, fn))
        if not d:
            reject("解析失败"); continue
        if not d["p"] or not re.match(r"^[a-züāáǎàēéěèīíǐìōóǒòūúǔùǖǘǚǜ]+(ng?|r)?$", d["p"]):
            reject("拼音缺失/异常"); continue
        if not d["k"] or not (2 <= int(d["k"]) <= 16):
            reject("笔画越界"); continue
        fm = re.search(r"(多用男性|多用女性|男女通用|中性|通用)", d["xingming"])
        if not fm:
            reject("无姓名学标记"); continue
        if d["fanjian"] and d["fanjian"] != ch:
            reject("简繁不同形(康熙笔画不可用总笔画)"); continue
        kept.append({"c": ch, "p": d["p"], "k": int(d["k"]),
                     "g": {"男": 1, "女": 2}.get(fm.group(1)[:1], 0),
                     "radical": d["radical"], "gloss": d["gloss"]})
        stats["kept"] += 1

    json.dump({"stats": stats, "kept": kept}, open(OUT, "w", encoding="utf-8"),
              ensure_ascii=False)
    print(json.dumps(stats, ensure_ascii=False))
    print("样例:", json.dumps(kept[:3], ensure_ascii=False))
    # 按部首分布
    from collections import Counter
    rc = Counter(x["radical"] for x in kept)
    print("Top部首:", rc.most_common(12))

if __name__ == "__main__":
    main()
