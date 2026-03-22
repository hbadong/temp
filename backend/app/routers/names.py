from fastapi import APIRouter
from pydantic import BaseModel
from typing import Optional

router = APIRouter(prefix="/api/names", tags=["名字库"])

COMMON_NAMES = [
    {'char': '子', 'pinyin': 'zi', 'bihua': 3, 'wuxing': '水', 'gender': 'M', 'meaning': '儿子、种子，古代对人的尊称', 'source': '古代称谓'},
    {'char': '墨', 'pinyin': 'mo', 'bihua': 14, 'wuxing': '土', 'gender': 'M', 'meaning': '笔墨、文学，借指读书学习', 'source': '文房四宝'},
    {'char': '梓', 'pinyin': 'zi', 'bihua': 11, 'wuxing': '木', 'gender': 'M', 'meaning': '树木名，古代常用于制作乐器，象征故乡', 'source': '植物名称'},
    {'char': '涵', 'pinyin': 'han', 'bihua': 11, 'wuxing': '水', 'gender': 'M', 'meaning': '包含、滋润，心胸宽广之意', 'source': '汉字释义'},
    {'char': '轩', 'pinyin': 'xuan', 'bihua': 10, 'wuxing': '土', 'gender': 'M', 'meaning': '古代有围棚的车，也指窗户或门', 'source': '古代器具'},
    {'char': '宇', 'pinyin': 'yu', 'bihua': 6, 'wuxing': '土', 'gender': 'M', 'meaning': '上下四方为宇，指房屋，也指天下', 'source': '宇宙天地'},
    {'char': '浩', 'pinyin': 'hao', 'bihua': 10, 'wuxing': '水', 'gender': 'M', 'meaning': '浩大、广阔，气势磅礴', 'source': '汉字释义'},
    {'char': '然', 'pinyin': 'ran', 'bihua': 12, 'wuxing': '金', 'gender': 'M', 'meaning': '如此、这样，也作为助词', 'source': '汉字释义'},
    {'char': '泽', 'pinyin': 'ze', 'bihua': 8, 'wuxing': '水', 'gender': 'M', 'meaning': '水聚集的地方，恩泽、恩惠', 'source': '自然现象'},
    {'char': '博', 'pinyin': 'bo', 'bihua': 12, 'wuxing': '水', 'gender': 'M', 'meaning': '广博、丰富，学识渊博', 'source': '汉字释义'},
    {'char': '雅', 'pinyin': 'ya', 'bihua': 12, 'wuxing': '木', 'gender': 'M', 'meaning': '高尚不俗，美好文雅', 'source': '汉字释义'},
    {'char': '文', 'pinyin': 'wen', 'bihua': 4, 'wuxing': '水', 'gender': 'M', 'meaning': '文字、文化，纹理', 'source': '汉字释义'},
    {'char': '明', 'pinyin': 'ming', 'bihua': 8, 'wuxing': '水', 'gender': 'M', 'meaning': '明亮、清晰，聪明贤明', 'source': '汉字释义'},
    {'char': '静', 'pinyin': 'jing', 'bihua': 16, 'wuxing': '金', 'gender': 'F', 'meaning': '安静、平静，不受打扰', 'source': '汉字释义'},
    {'char': '欣', 'pinyin': 'xin', 'bihua': 8, 'wuxing': '木', 'gender': 'F', 'meaning': '喜悦、快乐，茂盛样子', 'source': '汉字释义'},
    {'char': '怡', 'pinyin': 'yi', 'bihua': 9, 'wuxing': '土', 'gender': 'F', 'meaning': '和悦、愉快，心神愉悦', 'source': '汉字释义'},
    {'char': '嘉', 'pinyin': 'jia', 'bihua': 14, 'wuxing': '木', 'gender': 'M', 'meaning': '美好、善美，赞许', 'source': '汉字释义'},
    {'char': '琪', 'pinyin': 'qi', 'bihua': 12, 'wuxing': '木', 'gender': 'F', 'meaning': '美玉，珍贵之物', 'source': '珍宝名称'},
    {'char': '琳', 'pinyin': 'lin', 'bihua': 12, 'wuxing': '木', 'gender': 'F', 'meaning': '美玉，青碧色玉', 'source': '珍宝名称'},
    {'char': '瑶', 'pinyin': 'yao', 'bihua': 13, 'wuxing': '火', 'gender': 'F', 'meaning': '美玉，形容美好珍贵', 'source': '珍宝名称'},
    {'char': '瑾', 'pinyin': 'jin', 'bihua': 15, 'wuxing': '火', 'gender': 'F', 'meaning': '美玉，比喻美德', 'source': '珍宝名称'},
    {'char': '华', 'pinyin': 'hua', 'bihua': 12, 'wuxing': '水', 'gender': 'M', 'meaning': '华丽、光彩，精华', 'source': '汉字释义'},
    {'char': '伟', 'pinyin': 'wei', 'bihua': 6, 'wuxing': '土', 'gender': 'M', 'meaning': '伟大、壮美，卓越', 'source': '汉字释义'},
    {'char': '杰', 'pinyin': 'jie', 'bihua': 12, 'wuxing': '木', 'gender': 'M', 'meaning': '才能出众的人', 'source': '汉字释义'},
    {'char': '思', 'pinyin': 'si', 'bihua': 9, 'wuxing': '金', 'gender': 'M', 'meaning': '思考、思念，想念', 'source': '汉字释义'},
    {'char': '志', 'pinyin': 'zhi', 'bihua': 7, 'wuxing': '火', 'gender': 'M', 'meaning': '志向、志愿，立志', 'source': '汉字释义'},
    {'char': '远', 'pinyin': 'yuan', 'bihua': 12, 'wuxing': '土', 'gender': 'M', 'meaning': '距离遥远，深远', 'source': '汉字释义'},
    {'char': '德', 'pinyin': 'de', 'bihua': 15, 'wuxing': '火', 'gender': 'M', 'meaning': '品德、道德，美德', 'source': '汉字释义'},
    {'char': '仁', 'pinyin': 'ren', 'bihua': 4, 'wuxing': '金', 'gender': 'M', 'meaning': '仁爱、仁慈，古代一种道德范畴', 'source': '儒家思想'},
    {'char': '诚', 'pinyin': 'cheng', 'bihua': 14, 'wuxing': '金', 'gender': 'M', 'meaning': '诚实、真诚，真心', 'source': '汉字释义'},
    {'char': '敏', 'pinyin': 'min', 'bihua': 11, 'wuxing': '水', 'gender': 'F', 'meaning': '敏捷、聪敏，反应快', 'source': '汉字释义'},
    {'char': '慧', 'pinyin': 'hui', 'bihua': 15, 'wuxing': '水', 'gender': 'F', 'meaning': '智慧、聪慧，有才智', 'source': '汉字释义'},
    {'char': '颖', 'pinyin': 'ying', 'bihua': 16, 'wuxing': '木', 'gender': 'F', 'meaning': '才能出众，聪敏过人', 'source': '汉字释义'},
    {'char': '岚', 'pinyin': 'lan', 'bihua': 12, 'wuxing': '土', 'gender': 'F', 'meaning': '山间的雾气', 'source': '自然现象'},
    {'char': '彤', 'pinyin': 'tong', 'bihua': 7, 'wuxing': '火', 'gender': 'F', 'meaning': '红色，象征喜庆', 'source': '汉字释义'},
    {'char': '霞', 'pinyin': 'xia', 'bihua': 17, 'wuxing': '水', 'gender': 'F', 'meaning': '日出日落时的云彩', 'source': '自然现象'},
    {'char': '雨', 'pinyin': 'yu', 'bihua': 8, 'wuxing': '水', 'gender': 'M', 'meaning': '雨水，润泽万物', 'source': '自然现象'},
    {'char': '露', 'pinyin': 'lu', 'bihua': 20, 'wuxing': '水', 'gender': 'F', 'meaning': '露水，滋润', 'source': '自然现象'},
    {'char': '云', 'pinyin': 'yun', 'bihua': 4, 'wuxing': '水', 'gender': 'M', 'meaning': '云彩，飘浮不定', 'source': '自然现象'},
    {'char': '飞', 'pinyin': 'fei', 'bihua': 9, 'wuxing': '水', 'gender': 'M', 'meaning': '飞翔，飞行', 'source': '汉字释义'},
    {'char': '龙', 'pinyin': 'long', 'bihua': 16, 'wuxing': '火', 'gender': 'M', 'meaning': '古代神兽，象征权贵', 'source': '神兽传说'},
    {'char': '凤', 'pinyin': 'feng', 'bihua': 14, 'wuxing': '水', 'gender': 'F', 'meaning': '古代神鸟，象征吉祥', 'source': '神兽传说'},
    {'char': '鹏', 'pinyin': 'peng', 'bihua': 19, 'wuxing': '水', 'gender': 'M', 'meaning': '古代大鸟，志向远大', 'source': '神兽传说'},
    {'char': '鹤', 'pinyin': 'he', 'bihua': 21, 'wuxing': '水', 'gender': 'M', 'meaning': '仙鹤，象征长寿高雅', 'source': '动物名称'},
    {'char': '林', 'pinyin': 'lin', 'bihua': 8, 'wuxing': '木', 'gender': 'M', 'meaning': '树林，森林', 'source': '自然植物'},
    {'char': '森', 'pinyin': 'sen', 'bihua': 12, 'wuxing': '木', 'gender': 'M', 'meaning': '树木众多，繁密', 'source': '自然植物'},
    {'char': '松', 'pinyin': 'song', 'bihua': 8, 'wuxing': '木', 'gender': 'M', 'meaning': '松树，象征坚强', 'source': '自然植物'},
    {'char': '梅', 'pinyin': 'mei', 'bihua': 11, 'wuxing': '木', 'gender': 'F', 'meaning': '梅花，象征坚强高洁', 'source': '自然植物'},
    {'char': '兰', 'pinyin': 'lan', 'bihua': 5, 'wuxing': '木', 'gender': 'F', 'meaning': '兰花，象征君子', 'source': '自然植物'},
    {'char': '竹', 'pinyin': 'zhu', 'bihua': 6, 'wuxing': '木', 'gender': 'M', 'meaning': '竹子，象征坚韧', 'source': '自然植物'},
    {'char': '菊', 'pinyin': 'ju', 'bihua': 11, 'wuxing': '木', 'gender': 'F', 'meaning': '菊花，象征隐逸', 'source': '自然植物'},
    {'char': '莲', 'pinyin': 'lian', 'bihua': 13, 'wuxing': '木', 'gender': 'F', 'meaning': '莲花，象征清白', 'source': '自然植物'},
    {'char': '山', 'pinyin': 'shan', 'bihua': 3, 'wuxing': '土', 'gender': 'M', 'meaning': '山峰，高大雄伟', 'source': '自然地理'},
    {'char': '峰', 'pinyin': 'feng', 'bihua': 10, 'wuxing': '土', 'gender': 'M', 'meaning': '山峰顶点', 'source': '自然地理'},
    {'char': '川', 'pinyin': 'chuan', 'bihua': 3, 'wuxing': '金', 'gender': 'M', 'meaning': '河流，平原', 'source': '自然地理'},
    {'char': '海', 'pinyin': 'hai', 'bihua': 10, 'wuxing': '水', 'gender': 'M', 'meaning': '大海，广阔无边', 'source': '自然地理'},
    {'char': '天', 'pinyin': 'tian', 'bihua': 4, 'wuxing': '火', 'gender': 'M', 'meaning': '天空，自然界', 'source': '自然地理'},
    {'char': '安', 'pinyin': 'an', 'bihua': 6, 'wuxing': '土', 'gender': 'F', 'meaning': '平安，安全', 'source': '汉字释义'},
    {'char': '宁', 'pinyin': 'ning', 'bihua': 14, 'wuxing': '火', 'gender': 'F', 'meaning': '安宁，宁静', 'source': '汉字释义'},
    {'char': '康', 'pinyin': 'kang', 'bihua': 11, 'wuxing': '木', 'gender': 'M', 'meaning': '健康，安康', 'source': '汉字释义'},
    {'char': '泰', 'pinyin': 'tai', 'bihua': 9, 'wuxing': '火', 'gender': 'M', 'meaning': '平安，安定', 'source': '汉字释义'},
    {'char': '福', 'pinyin': 'fu', 'bihua': 12, 'wuxing': '水', 'gender': 'M', 'meaning': '幸福，福气', 'source': '汉字释义'},
    {'char': '翔', 'pinyin': 'xiang', 'bihua': 12, 'wuxing': '金', 'gender': 'M', 'meaning': '展开翅膀飞，翱翔', 'source': '汉字释义'},
    {'char': '勇', 'pinyin': 'yong', 'bihua': 9, 'wuxing': '土', 'gender': 'M', 'meaning': '勇敢，勇气', 'source': '汉字释义'},
    {'char': '俊', 'pinyin': 'jun', 'bihua': 9, 'wuxing': '火', 'gender': 'M', 'meaning': '俊秀，才智过人', 'source': '汉字释义'},
    {'char': '婷', 'pinyin': 'ting', 'bihua': 12, 'wuxing': '火', 'gender': 'F', 'meaning': '美好，亭亭玉立', 'source': '汉字释义'},
    {'char': '妍', 'pinyin': 'yan', 'bihua': 7, 'wuxing': '水', 'gender': 'F', 'meaning': '美丽，聪慧', 'source': '汉字释义'},
    {'char': '娜', 'pinyin': 'na', 'bihua': 10, 'wuxing': '火', 'gender': 'F', 'meaning': '袅娜，柔美', 'source': '汉字释义'},
    {'char': '婉', 'pinyin': 'wan', 'bihua': 11, 'wuxing': '土', 'gender': 'F', 'meaning': '温柔，和顺', 'source': '汉字释义'},
    {'char': '淑', 'pinyin': 'shu', 'bihua': 12, 'wuxing': '水', 'gender': 'F', 'meaning': '温和，善良', 'source': '汉字释义'},
    {'char': '洁', 'pinyin': 'jie', 'bihua': 9, 'wuxing': '水', 'gender': 'F', 'meaning': '洁净，干净', 'source': '汉字释义'},
    {'char': '素', 'pinyin': 'su', 'bihua': 10, 'wuxing': '金', 'gender': 'F', 'meaning': '朴素，本色', 'source': '汉字释义'},
    {'char': '真', 'pinyin': 'zhen', 'bihua': 10, 'wuxing': '金', 'gender': 'F', 'meaning': '真实，真诚', 'source': '汉字释义'},
    {'char': '善', 'pinyin': 'shan', 'bihua': 12, 'wuxing': '金', 'gender': 'F', 'meaning': '善良，美好', 'source': '汉字释义'},
    {'char': '美', 'pinyin': 'mei', 'bihua': 9, 'wuxing': '水', 'gender': 'F', 'meaning': '美丽，美好', 'source': '汉字释义'},
    {'char': '秀', 'pinyin': 'xiu', 'bihua': 7, 'wuxing': '金', 'gender': 'F', 'meaning': '秀美，优异', 'source': '汉字释义'},
    {'char': '丽', 'pinyin': 'li', 'bihua': 7, 'wuxing': '火', 'gender': 'F', 'meaning': '美丽，华丽', 'source': '汉字释义'},
    {'char': '倩', 'pinyin': 'qian', 'bihua': 10, 'wuxing': '金', 'gender': 'F', 'meaning': '美丽，含笑', 'source': '汉字释义'},
    {'char': '阳', 'pinyin': 'yang', 'bihua': 6, 'wuxing': '土', 'gender': 'M', 'meaning': '阳光，温暖', 'source': '自然现象'},
    {'char': '光', 'pinyin': 'guang', 'bihua': 6, 'wuxing': '火', 'gender': 'M', 'meaning': '光芒，光明', 'source': '自然现象'},
    {'char': '辉', 'pinyin': 'hui', 'bihua': 12, 'wuxing': '火', 'gender': 'M', 'meaning': '光辉，辉煌', 'source': '自然现象'},
    {'char': '耀', 'pinyin': 'yao', 'bihua': 20, 'wuxing': '火', 'gender': 'M', 'meaning': '照耀，光彩夺目', 'source': '自然现象'},
    {'char': '盛', 'pinyin': 'sheng', 'bihua': 12, 'wuxing': '金', 'gender': 'M', 'meaning': '繁盛，旺盛', 'source': '汉字释义'},
    {'char': '旺', 'pinyin': 'wang', 'bihua': 8, 'wuxing': '火', 'gender': 'M', 'meaning': '兴盛，旺盛', 'source': '汉字释义'},
    {'char': '昌', 'pinyin': 'chang', 'bihua': 8, 'wuxing': '火', 'gender': 'M', 'meaning': '兴旺，繁盛', 'source': '汉字释义'},
    {'char': '旭', 'pinyin': 'xu', 'bihua': 6, 'wuxing': '木', 'gender': 'M', 'meaning': '旭日东升，光明', 'source': '自然现象'},
    {'char': '晨', 'pinyin': 'chen', 'bihua': 11, 'wuxing': '金', 'gender': 'M', 'meaning': '早晨，清晨', 'source': '自然现象'},
    {'char': '凌', 'pinyin': 'ling', 'bihua': 10, 'wuxing': '火', 'gender': 'M', 'meaning': '冰，升高', 'source': '自然现象'},
    {'char': '霄', 'pinyin': 'xiao', 'bihua': 15, 'wuxing': '水', 'gender': 'M', 'meaning': '云，天空', 'source': '自然现象'},
    {'char': '宇', 'pinyin': 'yu', 'bihua': 6, 'wuxing': '土', 'gender': 'M', 'meaning': '上下四方为宇，指房屋，也指天下', 'source': '宇宙天地'},
    {'char': '瀚', 'pinyin': 'han', 'bihua': 20, 'wuxing': '水', 'gender': 'M', 'meaning': '广大，浩瀚', 'source': '汉字释义'},
    {'char': '铂', 'pinyin': 'bo', 'bihua': 13, 'wuxing': '金', 'gender': 'M', 'meaning': '金属元素', 'source': '化学元素'},
]

class NameQuery(BaseModel):
    gender: Optional[str] = None
    wuxing: Optional[str] = None
    limit: int = 20

@router.get("")
async def get_names(gender: str = None, wuxing: str = None, limit: int = 20):
    results = COMMON_NAMES
    
    if gender:
        results = [n for n in results if n['gender'] == gender.upper()]
    
    if wuxing:
        results = [n for n in results if n['wuxing'] == wuxing]
    
    return {
        'names': results[:limit],
        'total': len(results)
    }

@router.post("/recommend")
async def recommend_names(surname: str, gender: str = None, missing: list = None, limit: int = 10):
    results = COMMON_NAMES
    
    if gender:
        results = [n for n in results if n['gender'] == gender.upper()]
    
    if missing:
        results = [n for n in results if n['wuxing'] in missing]
    
    results.sort(key=lambda x: x['bihua'], reverse=True)
    
    return {
        'recommendations': results[:limit],
        'total': len(results)
    }
