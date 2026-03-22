from app.utils.pinyin import analyze_yinyun, get_pinyin
from app.utils.bihua import calc_name_bihua, get_bihua
from app.utils.wuxing import calc_name_wuxing, get_char_wuxing
from app.services.sancai import analyze_wuge

CHAR_MEANINGS = {
    '子': {'meaning': '儿子、种子，古代对人的尊称', 'source': '古代称谓'},
    '墨': {'meaning': '笔墨、文学，借指读书学习', 'source': '文房四宝'},
    '梓': {'meaning': '树木名，古代常用于制作乐器，象征故乡', 'source': '植物名称'},
    '轩': {'meaning': '古代有围棚的车，也指窗户或门', 'source': '古代器具'},
    '涵': {'meaning': '包含、滋润，心胸宽广之意', 'source': '汉字释义'},
    '宇': {'meaning': '上下四方为宇，指房屋，也指天下', 'source': '宇宙天地'},
    '浩': {'meaning': '浩大、广阔，气势磅礴', 'source': '汉字释义'},
    '然': {'meaning': '如此、这样，也作为助词', 'source': '汉字释义'},
    '泽': {'meaning': '水聚集的地方，恩泽、恩惠', 'source': '自然现象'},
    '霖': {'meaning': '连下几日的雨，比喻恩泽', 'source': '自然现象'},
    '博': {'meaning': '广博、丰富，学识渊博', 'source': '汉字释义'},
    '雅': {'meaning': '高尚不俗，美好文雅', 'source': '汉字释义'},
    '文': {'meaning': '文字、文化，纹理', 'source': '汉字释义'},
    '明': {'meaning': '明亮、清晰，聪明贤明', 'source': '汉字释义'},
    '静': {'meaning': '安静、平静，不受打扰', 'source': '汉字释义'},
    '欣': {'meaning': '喜悦、快乐，茂盛样子', 'source': '汉字释义'},
    '怡': {'meaning': '和悦、愉快，心神愉悦', 'source': '汉字释义'},
    '嘉': {'meaning': '美好、善美，赞许', 'source': '汉字释义'},
    '琪': {'meaning': '美玉，珍贵之物', 'source': '珍宝名称'},
    '琳': {'meaning': '美玉，青碧色玉', 'source': '珍宝名称'},
    '瑶': {'meaning': '美玉，形容美好珍贵', 'source': '珍宝名称'},
    '瑾': {'meaning': '美玉，比喻美德', 'source': '珍宝名称'},
    '璐': {'meaning': '美玉，玉名', 'source': '珍宝名称'},
    '华': {'meaning': '华丽、光彩，精华', 'source': '汉字释义'},
    '伟': {'meaning': '伟大、壮美，卓越', 'source': '汉字释义'},
    '军': {'meaning': '军队、军人', 'source': '汉字释义'},
    '杰': {'meaning': '才能出众的人', 'source': '汉字释义'},
    '思': {'meaning': '思考、思念，想念', 'source': '汉字释义'},
    '念': {'meaning': '想念、念头', 'source': '汉字释义'},
    '志': {'meaning': '志向、志愿，立志', 'source': '汉字释义'},
    '远': {'meaning': '距离遥远，深远', 'source': '汉字释义'},
    '承': {'meaning': '接受、继承，担当', 'source': '汉字释义'},
    '德': {'meaning': '品德、道德，美德', 'source': '汉字释义'},
    '仁': {'meaning': '仁爱、仁慈，古代一种道德范畴', 'source': '儒家思想'},
    '义': {'meaning': '正义、义气，道义', 'source': '儒家思想'},
    '忠': {'meaning': '忠诚、忠厚，尽心尽力', 'source': '汉字释义'},
    '诚': {'meaning': '诚实、真诚，真心', 'source': '汉字释义'},
    '敏': {'meaning': '敏捷、聪敏，反应快', 'source': '汉字释义'},
    '慧': {'meaning': '智慧、聪慧，有才智', 'source': '汉字释义'},
    '颖': {'meaning': '才能出众，聪敏过人', 'source': '汉字释义'},
    '岚': {'meaning': '山间的雾气', 'source': '自然现象'},
    '彤': {'meaning': '红色，象征喜庆', 'source': '汉字释义'},
    '霞': {'meaning': '日出日落时的云彩', 'source': '自然现象'},
    '雨': {'meaning': '雨水，润泽万物', 'source': '自然现象'},
    '露': {'meaning': '露水，滋润', 'source': '自然现象'},
    '风': {'meaning': '空气流动，风尚', 'source': '自然现象'},
    '云': {'meaning': '云彩，飘浮不定', 'source': '自然现象'},
    '翔': {'meaning': '展开翅膀飞，翱翔', 'source': '汉字释义'},
    '飞': {'meaning': '飞翔，飞行', 'source': '汉字释义'},
    '龙': {'meaning': '古代神兽，象征权贵', 'source': '神兽传说'},
    '凤': {'meaning': '古代神鸟，象征吉祥', 'source': '神兽传说'},
    '鹏': {'meaning': '古代大鸟，志向远大', 'source': '神兽传说'},
    '鹤': {'meaning': '仙鹤，象征长寿高雅', 'source': '动物名称'},
    '林': {'meaning': '树林，森林', 'source': '自然植物'},
    '森': {'meaning': '树木众多，繁密', 'source': '自然植物'},
    '松': {'meaning': '松树，象征坚强', 'source': '自然植物'},
    '柏': {'meaning': '柏树，象征长寿', 'source': '自然植物'},
    '梅': {'meaning': '梅花，象征坚强高洁', 'source': '自然植物'},
    '兰': {'meaning': '兰花，象征君子', 'source': '自然植物'},
    '竹': {'meaning': '竹子，象征坚韧', 'source': '自然植物'},
    '菊': {'meaning': '菊花，象征隐逸', 'source': '自然植物'},
    '莲': {'meaning': '莲花，象征清白', 'source': '自然植物'},
    '花': {'meaning': '花朵，美丽芳香', 'source': '自然植物'},
    '草': {'meaning': '小草，生命力强', 'source': '自然植物'},
    '山': {'meaning': '山峰，高大雄伟', 'source': '自然地理'},
    '峰': {'meaning': '山峰顶点', 'source': '自然地理'},
    '川': {'meaning': '河流，平原', 'source': '自然地理'},
    '海': {'meaning': '大海，广阔无边', 'source': '自然地理'},
    '江': {'meaning': '大河', 'source': '自然地理'},
    '河': {'meaning': '河流', 'source': '自然地理'},
    '湖': {'meaning': '湖泊，水平如镜', 'source': '自然地理'},
    '泉': {'meaning': '泉水，清澈', 'source': '自然地理'},
    '溪': {'meaning': '小溪流水', 'source': '自然地理'},
    '天': {'meaning': '天空，自然界', 'source': '自然地理'},
    '日': {'meaning': '太阳，光明', 'source': '自然地理'},
    '月': {'meaning': '月亮，柔美', 'source': '自然地理'},
    '星': {'meaning': '星星，闪烁', 'source': '自然地理'},
    '光': {'meaning': '光芒，光明', 'source': '自然现象'},
    '辉': {'meaning': '光辉，辉煌', 'source': '自然现象'},
    '耀': {'meaning': '照耀，光彩夺目', 'source': '自然现象'},
    '安': {'meaning': '平安，安全', 'source': '汉字释义'},
    '宁': {'meaning': '安宁，宁静', 'source': '汉字释义'},
    '定': {'meaning': '安定，决定', 'source': '汉字释义'},
    '平': {'meaning': '平安，平衡', 'source': '汉字释义'},
    '和': {'meaning': '和谐，和平', 'source': '汉字释义'},
    '乐': {'meaning': '快乐，音乐', 'source': '汉字释义'},
    '康': {'meaning': '健康，安康', 'source': '汉字释义'},
    '泰': {'meaning': '平安，安定', 'source': '汉字释义'},
    '福': {'meaning': '幸福，福气', 'source': '汉字释义'},
    '禄': {'meaning': '福禄，俸禄', 'source': '汉字释义'},
    '寿': {'meaning': '长寿，寿命', 'source': '汉字释义'},
    '喜': {'meaning': '喜悦，喜庆', 'source': '汉字释义'},
    '祥': {'meaning': '吉祥，祥瑞', 'source': '汉字释义'},
    '瑞': {'meaning': '祥瑞，吉利', 'source': '汉字释义'},
    '吉': {'meaning': '吉祥，顺利', 'source': '汉字释义'},
    '庆': {'meaning': '庆祝，庆贺', 'source': '汉字释义'},
    '勇': {'meaning': '勇敢，勇气', 'source': '汉字释义'},
    '刚': {'meaning': '刚强，坚硬', 'source': '汉字释义'},
    '强': {'meaning': '强大，强壮', 'source': '汉字释义'},
    '健': {'meaning': '健康，强健', 'source': '汉字释义'},
    '君': {'meaning': '君主，君子', 'source': '汉字释义'},
    '臣': {'meaning': '臣子，官员', 'source': '汉字释义'},
    '玉': {'meaning': '美玉，珍贵', 'source': '珍宝名称'},
    '珍': {'meaning': '珍贵，宝贝', 'source': '珍宝名称'},
    '珠': {'meaning': '珍珠，圆润', 'source': '珍宝名称'},
    '宝': {'meaning': '宝贝，珍贵', 'source': '珍宝名称'},
    '珊': {'meaning': '珊瑚，美丽', 'source': '珍宝名称'},
    '银': {'meaning': '白银，珍贵', 'source': '珍宝名称'},
    '金': {'meaning': '黄金，财富', 'source': '珍宝名称'},
    '红': {'meaning': '红色，热烈', 'source': '颜色'},
    '绿': {'meaning': '绿色，生机', 'source': '颜色'},
    '青': {'meaning': '青色，年轻', 'source': '颜色'},
    '蓝': {'meaning': '蓝色，宁静', 'source': '颜色'},
    '紫': {'meaning': '紫色，高贵', 'source': '颜色'},
    '白': {'meaning': '白色，纯真', 'source': '颜色'},
    '黄': {'meaning': '黄色，尊贵', 'source': '颜色'},
    '书': {'meaning': '书籍，书写', 'source': '文化'},
    '画': {'meaning': '绘画，图画', 'source': '文化'},
    '诗': {'meaning': '诗歌，诗词', 'source': '文化'},
    '琴': {'meaning': '古琴，音乐', 'source': '文化'},
    '棋': {'meaning': '棋艺，智慧', 'source': '文化'},
    '经': {'meaning': '经典，经书', 'source': '文化'},
    '典': {'meaning': '典籍，法典', 'source': '文化'},
    '学': {'meaning': '学习，学问', 'source': '文化'},
    '问': {'meaning': '询问，探讨', 'source': '文化'},
    '思': {'meaning': '思考，思想', 'source': '文化'},
    '悟': {'meaning': '领悟，觉悟', 'source': '文化'},
    '知': {'meaning': '知道，知识', 'source': '文化'},
    '行': {'meaning': '行走，行为', 'source': '汉字释义'},
    '言': {'meaning': '言语，说话', 'source': '汉字释义'},
    '语': {'meaning': '语言，谈论', 'source': '汉字释义'},
    '可': {'meaning': '可以，允许', 'source': '汉字释义'},
    '兮': {'meaning': '语气助词，优雅', 'source': '汉字释义'},
    '若': {'meaning': '如同，似乎', 'source': '汉字释义'},
    '之': {'meaning': '助词，的', 'source': '汉字释义'},
    '也': {'meaning': '语气助词', 'source': '汉字释义'},
    '乎': {'meaning': '语气助词，吗', 'source': '汉字释义'},
    '矣': {'meaning': '语气助词', 'source': '汉字释义'},
    '然': {'meaning': '如此，对的', 'source': '汉字释义'},
    '夫': {'meaning': '丈夫，男子', 'source': '汉字释义'},
    '乃': {'meaning': '于是，就', 'source': '汉字释义'},
    '尔': {'meaning': '如此，你', 'source': '汉字释义'},
    '斯': {'meaning': '这，这个', 'source': '汉字释义'},
    '此': {'meaning': '这，这个', 'source': '汉字释义'},
    '彼': {'meaning': '那，那个', 'source': '汉字释义'},
    '其': {'meaning': '他的，那个', 'source': '汉字释义'},
    '所': {'meaning': '地方，处所', 'source': '汉字释义'},
    '者': {'meaning': '的人，的东西', 'source': '汉字释义'},
    '阳': {'meaning': '阳光，温暖', 'source': '自然现象'},
    '阴': {'meaning': '阴影，阴暗', 'source': '自然现象'},
    '清': {'meaning': '清澈，纯净', 'source': '汉字释义'},
    '浊': {'meaning': '浑浊，污浊', 'source': '汉字释义'},
    '洁': {'meaning': '洁净，干净', 'source': '汉字释义'},
    '净': {'meaning': '干净，纯净', 'source': '汉字释义'},
    '素': {'meaning': '朴素，本色', 'source': '汉字释义'},
    '纯': {'meaning': '纯粹，纯正', 'source': '汉字释义'},
    '真': {'meaning': '真实，真诚', 'source': '汉字释义'},
    '善': {'meaning': '善良，美好', 'source': '汉字释义'},
    '美': {'meaning': '美丽，美好', 'source': '汉字释义'},
    '秀': {'meaning': '秀美，优异', 'source': '汉字释义'},
    '丽': {'meaning': '美丽，华丽', 'source': '汉字释义'},
    '俊': {'meaning': '俊秀，才智过人', 'source': '汉字释义'},
    '俏': {'meaning': '俊俏，美观', 'source': '汉字释义'},
    '美': {'meaning': '美丽，美好', 'source': '汉字释义'},
    '倩': {'meaning': '美丽，含笑', 'source': '汉字释义'},
    '婷': {'meaning': '美好，亭亭玉立', 'source': '汉字释义'},
    '妍': {'meaning': '美丽，聪慧', 'source': '汉字释义'},
    '媚': {'meaning': '美好，可爱', 'source': '汉字释义'},
    '娇': {'meaning': '娇美，娇柔', 'source': '汉字释义'},
    '娜': {'meaning': '袅娜，柔美', 'source': '汉字释义'},
    '婉': {'meaning': '温柔，和顺', 'source': '汉字释义'},
    '娴': {'meaning': '文静，熟练', 'source': '汉字释义'},
    '淑': {'meaning': '温和，善良', 'source': '汉字释义'},
    '贞': {'meaning': '坚贞，忠诚', 'source': '汉字释义'},
    '烈': {'meaning': '刚烈，壮烈', 'source': '汉字释义'},
    '雄': {'meaning': '雄伟，强大', 'source': '汉字释义'},
    '威': {'meaning': '威严，威武', 'source': '汉字释义'},
    '武': {'meaning': '武力，勇武', 'source': '汉字释义'},
    '猛': {'meaning': '勇猛，威猛', 'source': '汉字释义'},
    '豪': {'meaning': '豪爽，豪迈', 'source': '汉字释义'},
    '杰': {'meaning': '杰出，卓越', 'source': '汉字释义'},
    '俊': {'meaning': '俊秀，美丽', 'source': '汉字释义'},
    '崇': {'meaning': '崇高，尊重', 'source': '汉字释义'},
    '峻': {'meaning': '高大，严厉', 'source': '汉字释义'},
    '嵩': {'meaning': '高山，中岳', 'source': '地理'},
    '巍': {'meaning': '高大，巍峨', 'source': '汉字释义'},
    '岳': {'meaning': '高山，大山', 'source': '地理'},
    '岩': {'meaning': '岩石，险峻', 'source': '地理'},
    '峰': {'meaning': '山峰，顶点', 'source': '地理'},
    '岭': {'meaning': '山岭，连绵', 'source': '地理'},
    '涛': {'meaning': '波涛，浪花', 'source': '自然'},
    '澜': {'meaning': '波澜，大波', 'source': '自然'},
    '波': {'meaning': '波浪，水波', 'source': '自然'},
    '涌': {'meaning': '涌出，喷涌', 'source': '自然'},
    '泉': {'meaning': '泉水，水源', 'source': '自然'},
    '溪': {'meaning': '小溪，流水', 'source': '自然'},
    '源': {'meaning': '来源，根源', 'source': '汉字释义'},
    '瀚': {'meaning': '广大，浩瀚', 'source': '汉字释义'},
    '洋': {'meaning': '海洋，广大', 'source': '地理'},
    '溢': {'meaning': '溢出，满溢', 'source': '汉字释义'},
    '润': {'meaning': '滋润，光润', 'source': '汉字释义'},
    '注': {'meaning': '注入，注视', 'source': '汉字释义'},
    '滴': {'meaning': '水滴，一点', 'source': '汉字释义'},
    '游': {'meaning': '游泳，游玩', 'source': '汉字释义'},
    '泳': {'meaning': '游泳，潜行', 'source': '汉字释义'},
    '浮': {'meaning': '漂浮，浮动', 'source': '汉字释义'},
    '沉': {'meaning': '沉没，沉重', 'source': '汉字释义'},
    '洲': {'meaning': '陆地，洲际', 'source': '地理'},
    '波': {'meaning': '波浪', 'source': '自然'},
}

def get_char_meaning(char: str) -> dict:
    if char in CHAR_MEANINGS:
        return CHAR_MEANINGS[char]
    return {'meaning': '含义待查', 'source': '未知'}

def analyze_hanyi(name: str) -> dict:
    meanings = []
    for char in name:
        meaning_info = get_char_meaning(char)
        meanings.append({
            'char': char,
            'pinyin': get_pinyin(char),
            'meaning': meaning_info['meaning'],
            'source': meaning_info['source']
        })
    
    combined_meaning = '、'.join([m['meaning'] for m in meanings])
    
    return {
        'meanings': meanings,
        'combined_meaning': combined_meaning
    }

def calc_yinyun_score(name: str) -> int:
    yinyun = analyze_yinyun(name)
    tones = yinyun['tones']
    
    if len(tones) < 2:
        return 85
    
    score = 75
    
    for i in range(len(tones) - 1):
        diff = abs(tones[i] - tones[i+1])
        if diff == 0:
            score += 3
        elif diff == 1:
            score += 5
        elif diff == 2:
            score += 4
        else:
            score += 1
    
    tone_combinations = [
        (1, 2), (1, 3), (2, 3), (3, 1),
        (1, 4), (2, 4), (3, 4),
        (4, 1), (4, 2), (4, 3)
    ]
    for i in range(len(tones) - 1):
        if (tones[i], tones[i+1]) in tone_combinations:
            score += 5
    
    if len(set(tones)) == len(tones):
        score += 5
    
    return min(100, max(60, score))

def calc_zixing_score(name: str) -> int:
    bihua_list = calc_name_bihua(name)
    
    if len(bihua_list) < 2:
        return 80
    
    score = 70
    
    for i in range(len(bihua_list) - 1):
        diff = abs(bihua_list[i] - bihua_list[i+1])
        if 1 <= diff <= 3:
            score += 8
        elif diff == 0:
            score += 5
        elif diff <= 5:
            score += 3
        else:
            score += 1
    
    for bh in bihua_list:
        if 8 <= bh <= 12:
            score += 5
            break
    
    return min(100, max(60, score))

def calc_hanyi_score(name: str) -> int:
    hanzi_data = analyze_hanyi(name)
    meanings = hanzi_data['meanings']
    
    score = 70
    
    positive_keywords = ['美', '好', '善', '福', '禄', '寿', '康', '宁', '安', '吉', '祥', '瑞', '德', '仁', '义', '忠', '诚', '勇', '智', '慧', '杰', '伟', '豪', '雄', '俊', '秀', '丽', '华', '珍', '宝', '玉', '珠', '琳', '瑶', '琪', '静', '雅', '怡', '欣', '乐', '和', '平', '正', '直', '刚', '强', '健', '明', '光', '辉', '耀', '荣', '兴', '旺', '盛', '隆', '昌', '腾', '飞', '翔', '龙', '凤', '鹏', '鹤']
    
    for m in meanings:
        if any(kw in m['meaning'] for kw in positive_keywords):
            score += 8
        if '长寿' in m['meaning'] or '吉祥' in m['meaning'] or '平安' in m['meaning']:
            score += 10
    
    return min(100, max(60, score))

def analyze_name(surname: str, given_name: str, missing: list = None) -> dict:
    full_name = surname + given_name
    
    yinyun = analyze_yinyun(full_name)
    yinyun_score = calc_yinyun_score(full_name)
    
    bihua = calc_name_bihua(full_name)
    zixing_score = calc_zixing_score(full_name)
    
    hanzi = analyze_hanyi(full_name)
    hanzi_score = calc_hanyi_score(full_name)
    
    wuge_result = analyze_wuge(surname, given_name)
    
    wuxing_list = calc_name_wuxing(full_name)
    
    base_score = int(yinyun_score * 0.3 + zixing_score * 0.2 + hanzi_score * 0.3 + wuge_result['sancai_score'] * 0.2)
    
    wuxing_bonus = 0
    if missing:
        for wx in wuxing_list:
            if wx in missing:
                wuxing_bonus += 5
            else:
                wuxing_bonus -= 2
    
    final_score = min(100, base_score + wuxing_bonus)
    
    if final_score >= 90:
        grade = '优'
    elif final_score >= 75:
        grade = '良'
    elif final_score >= 60:
        grade = '中'
    else:
        grade = '差'
    
    evaluation = generate_evaluation(yinyun_score, zixing_score, hanzi_score, wuge_result['sancai_score'], wuxing_bonus)
    
    return {
        'pinyin': yinyun['pinyin'],
        'initials': yinyun['initials'],
        'finals': yinyun['finals'],
        'tones': yinyun['tones'],
        'yinyun_score': yinyun_score,
        
        'bihua': bihua,
        'zixing_score': zixing_score,
        
        'hanzi_meanings': hanzi['meanings'],
        'hanyi_score': hanzi_score,
        
        'wuge': wuge_result['wuge'],
        'sancai_score': wuge_result['sancai_score'],
        'sancai_description': wuge_result['sancai_description'],
        
        'wuxing': wuxing_list,
        
        'base_score': base_score,
        'wuxing_bonus': wuxing_bonus,
        'final_score': final_score,
        'grade': grade,
        'evaluation': evaluation,
        'missing': missing or []
    }

def generate_evaluation(yinyun: int, zixing: int, hanzi: int, sancai: int, wuxing_bonus: int) -> str:
    parts = []
    
    if yinyun >= 85:
        parts.append("音韵和谐优美")
    elif yinyun >= 75:
        parts.append("音韵搭配尚可")
    else:
        parts.append("音韵搭配一般")
    
    if zixing >= 85:
        parts.append("字形结构协调")
    elif zixing >= 75:
        parts.append("字形搭配合理")
    else:
        parts.append("字形搭配一般")
    
    if hanzi >= 85:
        parts.append("寓意深远美好")
    elif hanzi >= 75:
        parts.append("寓意积极向上")
    else:
        parts.append("寓意中规中矩")
    
    if sancai >= 85:
        parts.append("三才配置大吉")
    elif sancai >= 75:
        parts.append("三才配置吉祥")
    else:
        parts.append("三才配置一般")
    
    if wuxing_bonus > 0:
        parts.append(f"五行加成+{wuxing_bonus}分")
    elif wuxing_bonus < 0:
        parts.append(f"五行需注意-{abs(wuxing_bonus)}分")
    
    return '，'.join(parts) + '。'
