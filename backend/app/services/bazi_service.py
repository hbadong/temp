from app.utils.tiangan import calc_bazi, calc_wuxing, get_missing_wuxing, get_wuxing_suggestions

def analyze_bazi(birth_year: int, birth_month: int, birth_day: int, birth_hour: int) -> dict:
    bazi = calc_bazi(birth_year, birth_month, birth_day, birth_hour)
    wuxing_count = calc_wuxing(bazi)
    missing = get_missing_wuxing(wuxing_count)
    suggestions = get_wuxing_suggestions(missing)
    
    return {
        'bazi': {
            'year': bazi['year'],
            'month': bazi['month'],
            'day': bazi['day'],
            'hour': bazi['hour']
        },
        'wuxing': {
            'metal': wuxing_count['金'],
            'wood': wuxing_count['木'],
            'water': wuxing_count['水'],
            'fire': wuxing_count['火'],
            'earth': wuxing_count['土']
        },
        'wuxing_list': [wuxing_count['木'], wuxing_count['火'], wuxing_count['土'], wuxing_count['金'], wuxing_count['水']],
        'missing': missing,
        'suggestions': suggestions
    }
