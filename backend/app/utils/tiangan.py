# 农历天干地支计算工具

TIANGAN = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸']
DIZHI = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥']

WUXING_MAP = {
    '甲': '木', '乙': '木',
    '丙': '火', '丁': '火',
    '戊': '土', '己': '土',
    '庚': '金', '辛': '金',
    '壬': '水', '癸': '水'
}

DIZHI_WUXING = {
    '子': '水', '丑': '土', '寅': '木', '卯': '木',
    '辰': '土', '巳': '火', '午': '火', '未': '土',
    '申': '金', '酉': '金', '戌': '土', '亥': '水'
}

def get_tiangan_index(year: int) -> int:
    return (year - 4) % 10

def get_dizhi_index(year: int) -> int:
    return (year - 4) % 12

def calc_year_zhu(year: int) -> tuple:
    tiangan_idx = get_tiangan_index(year)
    dizhi_idx = get_dizhi_index(year)
    return (TIANGAN[tiangan_idx], DIZHI[dizhi_idx])

MONTH_TIANGAN = {
    '甲': ['丙', '丁', '戊', '己', '庚', '辛', '壬', '癸', '甲', '乙', '丙', '丁'],
    '乙': ['戊', '己', '庚', '辛', '壬', '癸', '甲', '乙', '丙', '丁', '戊', '己'],
    '丙': ['庚', '辛', '壬', '癸', '甲', '乙', '丙', '丁', '戊', '己', '庚', '辛'],
    '丁': ['壬', '癸', '甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'],
    '戊': ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'],
    '己': ['丙', '丁', '戊', '己', '庚', '辛', '壬', '癸', '甲', '乙'],
    '庚': ['壬', '癸', '甲', '乙', '丙', '丁', '戊', '己', '庚', '辛'],
    '辛': ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'],
    '壬': ['丙', '丁', '戊', '己', '庚', '辛', '壬', '癸', '甲', '乙'],
    '癸': ['戊', '己', '庚', '辛', '壬', '癸', '甲', '乙', '丙', '丁']
}

DIZHI_MONTH = ['寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥', '子', '丑']

def calc_month_zhu(year_tiangan: str, month: int) -> tuple:
    if month < 1 or month > 12:
        raise ValueError("月份必须在1-12之间")
    tiangan_list = MONTH_TIANGAN.get(year_tiangan, [])
    if not tiangan_list:
        raise ValueError(f"无效的天干: {year_tiangan}")
    tiangan = tiangan_list[month - 1]
    dizhi = DIZHI_MONTH[month - 1]
    return (tiangan, dizhi)

RI_ZHU_TABLE = {}

def init_ri_zhu_table():
    pass

RI_TIANGAN = ['甲', '己', '乙', '庚', '丙', '辛', '丁', '壬', '戊', '癸']
RI_DIZHI = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥']

def calc_day_zhu(year: int, month: int, day: int) -> tuple:
    import datetime
    base_date = datetime.date(1900, 1, 1)
    target_date = datetime.date(year, month, day)
    days_diff = (target_date - base_date).days
    
    year_offset = 0
    for y in range(1900, year):
        if (y - 1900) % 60 == 0:
            year_offset = y - 1900
            break
    
    total_days = days_diff + year_offset
    day_tiangan = RI_TIANGAN[total_days % 10]
    day_dizhi = RI_DIZHI[total_days % 12]
    
    return (day_tiangan, day_dizhi)

HOUR_DIZHI = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥']

def calc_hour_zhu(day_tiangan: str, hour: int) -> tuple:
    if hour < 0 or hour > 23:
        raise ValueError("小时必须在0-23之间")
    
    hour_idx = (hour + 1) // 2 % 12
    
    dizhi = HOUR_DIZHI[hour_idx]
    tiangan_idx = (RI_TIANGAN.index(day_tiangan)) % 5
    tiangan_hour_map = {
        0: ['甲', '丙', '戊', '庚', '壬'],
        1: ['乙', '丁', '己', '辛', '癸'],
        2: ['丙', '戊', '庚', '壬', '甲'],
        3: ['丁', '己', '辛', '癸', '乙'],
        4: ['戊', '庚', '壬', '甲', '丙']
    }
    tiangan = tiangan_hour_map[tiangan_idx][hour_idx // 2]
    
    return (tiangan, dizhi)

def calc_bazi(birth_year: int, birth_month: int, birth_day: int, birth_hour: int) -> dict:
    year_zhu = calc_year_zhu(birth_year)
    month_zhu = calc_month_zhu(year_zhu[0], birth_month)
    day_zhu = calc_day_zhu(birth_year, birth_month, birth_day)
    hour_zhu = calc_hour_zhu(day_zhu[0], birth_hour)
    
    return {
        'year': ''.join(year_zhu),
        'month': ''.join(month_zhu),
        'day': ''.join(day_zhu),
        'hour': ''.join(hour_zhu),
        'year_tiangan': year_zhu[0],
        'month_tiangan': month_zhu[0],
        'day_tiangan': day_zhu[0],
        'hour_tiangan': hour_zhu[0],
        'year_dizhi': year_zhu[1],
        'month_dizhi': month_zhu[1],
        'day_dizhi': day_zhu[1],
        'hour_dizhi': hour_zhu[1]
    }

def calc_wuxing(bazi: dict) -> dict:
    wuxing_count = {'木': 0, '火': 0, '土': 0, '金': 0, '水': 0}
    
    tiangans = [bazi['year_tiangan'], bazi['month_tiangan'], bazi['day_tiangan'], bazi['hour_tiangan']]
    for t in tiangans:
        if t in WUXING_MAP:
            wuxing_count[WUXING_MAP[t]] += 1
    
    dizhis = [bazi['year_dizhi'], bazi['month_dizhi'], bazi['day_dizhi'], bazi['hour_dizhi']]
    for d in dizhis:
        if d in DIZHI_WUXING:
            wuxing_count[DIZHI_WUXING[d]] += 1
    
    return wuxing_count

def get_missing_wuxing(wuxing_count: dict) -> list:
    return [w for w, count in wuxing_count.items() if count == 0]

def get_wuxing_suggestions(missing: list) -> list:
    suggestions = []
    wuxing_relations = {
        '木': ['金', '水'],
        '火': ['木', '土'],
        '土': ['火', '金'],
        '金': ['土', '木'],
        '水': ['金', '火']
    }
    for m in missing:
        related = wuxing_relations.get(m, [])
        suggestions.append(f"宜补{m}，可选用{''.join(related)}属性的字")
    return suggestions
