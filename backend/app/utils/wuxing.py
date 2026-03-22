WUXING = ['金', '木', '水', '火', '土']

WUXING_XIANGSHENG = {
    '木': '火',
    '火': '土',
    '土': '金',
    '金': '水',
    '水': '木'
}

WUXING_XIANGKE = {
    '木': '土',
    '火': '金',
    '土': '水',
    '金': '火',
    '水': '木'
}

def get_wuxing_xiangsheng(wuxing: str) -> str:
    return WUXING_XIANGSHENG.get(wuxing, '')

def get_wuxing_xiangke(wuxing: str) -> str:
    return WUXING_XIANGKE.get(wuxing, '')

def is_wuxing_match(name_wuxing: str, missing: list) -> bool:
    return name_wuxing in missing

CHAR_WUXING = {
    '甲': '木', '乙': '木', '丙': '火', '丁': '火', '戊': '土',
    '己': '土', '庚': '金', '辛': '金', '壬': '水', '癸': '水',
    '子': '水', '丑': '土', '寅': '木', '卯': '木', '辰': '土',
    '巳': '火', '午': '火', '未': '土', '申': '金', '酉': '金',
    '戌': '土', '亥': '水'
}

def get_char_wuxing(char: str) -> str:
    return CHAR_WUXING.get(char, '土')

def calc_name_wuxing(name: str) -> list:
    result = []
    for char in name:
        wx = get_char_wuxing(char)
        result.append(wx)
    return result
