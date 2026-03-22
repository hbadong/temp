from app.utils.bihua import get_bihua, calc_surname_bihua, calc_name_bihua

WUGEN_JIXIONG = {
    1: '吉', 2: '凶', 3: '吉', 4: '凶', 5: '吉', 6: '半吉', 7: '吉', 8: '凶',
    9: '凶', 10: '凶', 11: '吉', 12: '凶', 13: '吉', 14: '凶', 15: '吉', 16: '吉',
    17: '半吉', 18: '吉', 19: '凶', 20: '凶', 21: '吉', 22: '凶', 23: '吉', 24: '吉',
    25: '吉', 26: '半吉', 27: '吉', 28: '凶', 29: '吉', 30: '凶', 31: '吉', 32: '吉',
    33: '吉', 34: '凶', 35: '吉', 36: '凶', 37: '吉', 38: '半吉', 39: '吉', 40: '凶',
    41: '吉', 42: '吉', 43: '吉', 44: '凶', 45: '吉', 46: '半吉', 47: '吉', 48: '吉',
    49: '凶', 50: '半吉', 51: '半吉', 52: '吉', 53: '半吉', 54: '凶', 55: '吉', 56: '半吉',
    57: '半吉', 58: '半吉', 59: '凶', 60: '凶', 61: '半吉', 62: '凶', 63: '吉', 64: '凶',
    65: '吉', 66: '半吉', 67: '吉', 68: '半吉', 69: '凶', 70: '凶', 71: '半吉', 72: '凶',
    73: '吉', 74: '凶', 75: '半吉', 76: '半吉', 77: '半吉', 78: '半吉', 79: '半吉', 80: '半吉',
    81: '吉'
}

def is_fuxing(surname: str) -> bool:
    common_fuxing = ['欧阳', '司马', '上官', '诸葛', '慕容', '令狐', '公孙', '东方', '南宫', '西门', '北堂', '安平', '太史', '申屠', '夏侯', '皇甫', '尉迟', '呼延', '归海', '段干', '百里', '东郭', '子车', '颛孙', '端木', '巫马', '公西', '漆雕', '乐正', '壤驷', '公良', '拓跋', '夹谷', '宰父', '谷利', 'UL', '空', '曾', '毐', '粥', '哈', '帽', '它', '宓', '丌官', '昃', '亓官', '狄', '米', '黉', '雍', '元', '干', '仉', '督', '晋', '楚', '闫', '萨', '仝', '邝', '夔', '牛', '拐', '错', '闰', '壬', '.ctx', '擅长', '.ctx', '擅长', '.ctx', '擅长', '.ctx', '擅长', '.ctx', '擅长']
    return surname in common_fuxing or len(surname) > 1

def calc_wuge(surname: str, given_name: str) -> dict:
    if is_fuxing(surname):
        return calc_fuxing_wuge(surname, given_name)
    else:
        return calc_danxing_wuge(surname, given_name)

def calc_danxing_wuge(surname: str, given_name: str) -> dict:
    surname_bihua = calc_surname_bihua(surname)
    name_bihua_list = calc_name_bihua(given_name)
    
    tiange = surname_bihua + 1
    
    renge = surname_bihua + name_bihua_list[0]
    
    if len(name_bihua_list) >= 2:
        dige = name_bihua_list[0] + name_bihua_list[1]
        waige = (renge % 10) + (dige % 10)
    else:
        dige = name_bihua_list[0]
        waige = (renge % 10) + 1
    
    zongge = surname_bihua + sum(name_bihua_list)
    
    return {
        'tiange': tiange,
        'renge': renge,
        'dige': dige,
        'waige': waige,
        'zongge': zongge
    }

def calc_fuxing_wuge(surname: str, given_name: str) -> dict:
    surname_bihua = calc_surname_bihua(surname)
    name_bihua_list = calc_name_bihua(given_name)
    
    tiange = surname_bihua
    
    renge = surname_bihua // 2 + name_bihua_list[0]
    
    dige = sum(name_bihua_list)
    
    zongge = surname_bihua + sum(name_bihua_list)
    
    waige = (zongge % 10) + 1
    
    return {
        'tiange': tiange,
        'renge': renge,
        'dige': dige,
        'waige': waige,
        'zongge': zongge
    }

def get_jixiong(wuge_num: int) -> str:
    if wuge_num <= 0 or wuge_num > 81:
        return '凶'
    return WUGEN_JIXIONG.get(wuge_num, '凶')

def calc_sancai_score(tiange: int, renge: int, dige: int) -> int:
    tiandi = ['木', '木', '火', '土', '金', '土', '金', '水', '木', '火', '土', '金', '水', '木', '火', '土', '金', '水', '木', '火']
    sancai_map = {
        ('木', '木'): 85, ('木', '火'): 95, ('木', '土'): 75, ('木', '金'): 65, ('木', '水'): 70,
        ('火', '木'): 95, ('火', '火'): 80, ('火', '土'): 90, ('火', '金'): 60, ('火', '水'): 75,
        ('土', '木'): 75, ('土', '火'): 90, ('土', '土'): 85, ('土', '金'): 95, ('土', '水'): 70,
        ('金', '木'): 65, ('金', '火'): 60, ('金', '土'): 95, ('金', '金'): 80, ('金', '水'): 85,
        ('水', '木'): 70, ('水', '火'): 75, ('水', '土'): 70, ('水', '金'): 85, ('水', '水'): 80,
    }
    
    tian_idx = (tiange - 1) % 20
    ren_idx = (renge - 1) % 20
    di_idx = (dige - 1) % 20
    
    tian = tiandi[tian_idx]
    ren = tiandi[ren_idx]
    di = tiandi[di_idx]
    
    score1 = sancai_map.get((tian, ren), 70)
    score2 = sancai_map.get((ren, di), 70)
    
    return (score1 + score2) // 2

def analyze_wuge(surname: str, given_name: str) -> dict:
    wuge = calc_wuge(surname, given_name)
    
    analysis = {}
    for key, value in wuge.items():
        analysis[key] = {
            'value': value,
            'jixiong': get_jixiong(value)
        }
    
    sancai_score = calc_sancai_score(wuge['tiange'], wuge['renge'], wuge['dige'])
    
    return {
        'wuge': analysis,
        'sancai_score': sancai_score,
        'sancai_description': get_sancai_description(wuge['tiange'], wuge['renge'], wuge['dige'])
    }

def get_sancai_description(tiange: int, renge: int, dige: int) -> str:
    tiandi = ['木', '木', '火', '土', '金', '土', '金', '水', '木', '火', '土', '金', '水', '木', '火', '土', '金', '水', '木', '火']
    
    tian_idx = (tiange - 1) % 20
    ren_idx = (renge - 1) % 20
    di_idx = (dige - 1) % 20
    
    tian = tiandi[tian_idx]
    ren = tiandi[ren_idx]
    di = tiandi[di_idx]
    
    if tian == ren and ren == di:
        return f"{tian}性三才一气，天地人同命同心，主大吉昌"
    elif tian == ren:
        return f"{tian}性一致，{ren}与{di}相生，主吉祥"
    elif ren == di:
        return f"{ren}性一致，{tian}与{di}相生，主吉利"
    elif tian == di:
        return f"{tian}性相同，{ren}性居中调和，主平稳"
    elif is_sheng(tian, ren) and is_sheng(ren, di):
        return f"{tian}生{ren}，{ren}生{di}，三才相生，主大吉"
    elif is_ke(tian, ren) and is_ke(ren, di):
        return f"{tian}克{ren}，{ren}克{di}，三才相克，主凶祸"
    else:
        return f"天{tian}人{ren}地{di}，三才配置一般，需结合八字综合评判"

def is_sheng(a: str, b: str) -> bool:
    return (a == '木' and b == '火') or (a == '火' and b == '土') or \
           (a == '土' and b == '金') or (a == '金' and b == '水') or \
           (a == '水' and b == '木')

def is_ke(a: str, b: str) -> bool:
    return (a == '木' and b == '土') or (a == '土' and b == '水') or \
           (a == '水' and b == '火') or (a == '火' and b == '金') or \
           (a == '金' and b == '木')
