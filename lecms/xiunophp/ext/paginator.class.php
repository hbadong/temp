<?php
class paginator{

    /**
     * 分页函数
     * @param int $page 当前页
     * @param int $maxpage 最大页
     * @param string $url 完整路径
     * @param int $offset 偏移数
     * @param array $lang 上下页数组
     * @return string
     */
    public static function pages($page = 1, $maxpage = 0, $url = '', $offset = 5, $lang = array('&#171;', '&#187;')){
        if($maxpage < 2) return '';
        $pnum = $offset*2;
        $ismore = $maxpage > $pnum;
        $s = '';
        $ua = explode('{page}', $url);
        if($page > 1) $s .= '<a href="'.$ua[0].($page-1).$ua[1].'">'.$lang[0].'</a>';
        if($ismore) {
            $i_end = min($maxpage, max($pnum, $page+$offset)) - 1;
            $i = max(2, $i_end-$pnum+2);
        }else{
            $i_end = min($maxpage, $pnum)-1;
            $i = 2;
        }
        $s .= $page == 1 ? '<b>1</b>' : '<a href="'.$ua[0].'1'.$ua[1].'">1</a>';
        for($i; $i<=$i_end; $i++){
            $s .= $page == $i ? '<b>'.$i.'</b>' : '<a href="'.$ua[0].$i.$ua[1].'">'.$i.'</a>';
        }
        $s .= $page == $maxpage ? '<b>'.$maxpage.'</b>' : '<a href="'.$ua[0].$maxpage.$ua[1].'">'.$maxpage.'</a>';
        if($page < $maxpage) $s .= '<a class="nextpage" href="'.$ua[0].($page+1).$ua[1].'">'.$lang[1].'</a>';
        return $s;
    }

    /**
     * layui分页函数
     * @param int $page 当前页
     * @param int $maxpage 最大页
     * @param string $url 完整路径
     * @param int $offset 偏移数
     * @param array $lang 上下页数组
     * @return string
     */
    public static function layui_pages($page = 1, $maxpage = 0, $url = '', $offset = 5, $lang = array('&#171;', '&#187;')){
        if($maxpage < 2) return '';
        $pnum = $offset*2;
        $ismore = $maxpage > $pnum;
        $s = '';
        $ua = explode('{page}', $url);
        if($page > 1){
            $s .= '<a class="layui-laypage-prev" href="'.$ua[0].($page-1).$ua[1].'" data-page="'.($page-1).'">'.$lang[0].'</a>';
        }else{
            $s .= '<a class="layui-laypage-prev layui-disabled" data-page="0">'.$lang[0].'</a>';
        }
        if($ismore) {
            $i_end = min($maxpage, max($pnum, $page+$offset)) - 1;
            $i = max(2, $i_end-$pnum+2);
        }else{
            $i_end = min($maxpage, $pnum)-1;
            $i = 2;
        }
        $s .= $page == 1 ? '<span class="layui-laypage-curr"><em class="layui-laypage-em"></em><em>1</em></span>' : '<a href="'.$ua[0].'1'.$ua[1].'" data-page="1">1</a>';
        for($i; $i<=$i_end; $i++){
            if($page == $i){
                $s .= '<span class="layui-laypage-curr"><em class="layui-laypage-em"></em><em>'.$i.'</em></span>';
            }else{
                $s .= '<a href="'.$ua[0].$i.$ua[1].'" data-page="'.$i.'">'.$i.'</a>';
            }
        }
        $s .= $page == $maxpage ? '<span class="layui-laypage-curr"><em class="layui-laypage-em"></em><em>'.$maxpage.'</em></span>' : '<a class="layui-laypage-last" href="'.$ua[0].$maxpage.$ua[1].'" data-page="'.$maxpage.'">'.$maxpage.'</a>';
        if($page < $maxpage){
            $s .= '<a class="layui-laypage-next" href="'.$ua[0].($page+1).$ua[1].'" data-page="'.($page+1).'">'.$lang[1].'</a>';
        }else{
            $s .= '<a class="layui-laypage-next layui-disabled" data-page="'.($page+1).'">'.$lang[1].'</a>';
        }
        return $s;
    }

    /**
     * 分页函数 bootstrap风格
     * @param int $page 当前页
     * @param int $maxpage 最大页
     * @param string $url 完整路径
     * @param int $offset 偏移数
     * @param array $lang 上下页数组
     * @return string
     */
    public static function pages_bootstrap($page = 1, $maxpage = 0, $url = '', $offset = 5, $lang = array('&#171;', '&#187;')){
        if($maxpage < 2) return '';
        $pnum = $offset*2;
        $ismore = $maxpage > $pnum;
        $s = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center flex-wrap">';
        $ua = explode('{page}', $url);
        if($page > 1) $s .= '<li class="page-item"><a class="page-link" href="'.$ua[0].($page-1).$ua[1].'">'.$lang[0].'</a></li>';
        if($ismore) {
            $i_end = min($maxpage, max($pnum, $page+$offset)) - 1;
            $i = max(2, $i_end-$pnum+2);
        }else{
            $i_end = min($maxpage, $pnum)-1;
            $i = 2;
        }
        $s .= $page == 1 ? '<li class="page-item active" aria-current="page"><span class="page-link">1</span></li>' : '<li class="page-item"><a class="page-link" href="'.$ua[0].'1'.$ua[1].'">1</a></li>';
        for($i; $i<=$i_end; $i++){
            $s .= $page == $i ? '<li class="page-item active" aria-current="page"><span class="page-link">'.$i.'</span></li>' : '<li class="page-item"><a class="page-link" href="'.$ua[0].$i.$ua[1].'">'.$i.'</a></li>';
        }
        $s .= $page == $maxpage ? '<li class="page-item active" aria-current="page"><span class="page-link">'.$maxpage.'</span></li>' : '<li class="page-item"><a class="page-link" href="'.$ua[0].$maxpage.$ua[1].'">'.$maxpage.'</a></li>';
        if($page < $maxpage) $s .= '<li class="page-item"><a class="nextpage page-link" href="'.$ua[0].($page+1).$ua[1].'">'.$lang[1].'</a></li>';

        $s .= '</ul></nav>';
        return $s;
    }

    /**
     * 分页函数 mui移动端风格
     * @param int $page 当前页
     * @param int $maxpage 最大页
     * @param string $url 完整路径
     * @param int $offset 偏移数
     * @param array $lang 上下页数组
     * @return string
     */
    public static function pages_mui($page = 1, $maxpage = 0, $url = '', $offset = 3, $lang = array('&laquo;', '&raquo;')){
        if($maxpage < 2) return '';

        $pnum = $offset*2;
        $ismore = $maxpage > $pnum;
        $s = '<ul class="mui-pagination">';
        $ua = explode('{page}', $url);
        if($page > 1) $s .= '<li class="mui-previous"><a href="'.$ua[0].($page-1).$ua[1].'">'.$lang[0].'</a></li>';
        if($ismore) {
            $i_end = min($maxpage, max($pnum, $page+$offset)) - 1;
            $i = max(2, $i_end-$pnum+2);
        }else{
            $i_end = min($maxpage, $pnum)-1;
            $i = 2;
        }
        $s .= $page == 1 ? '<li class="mui-active"><a href="#">1</a></li>' : '<li><a href="'.$ua[0].'1'.$ua[1].'">1</a></li>';
        for($i; $i<=$i_end; $i++){
            $s .= $page == $i ? '<li class="mui-active"><a href="#">'.$i.'</a></li>' : '<li><a href="'.$ua[0].$i.$ua[1].'">'.$i.'</a></li>';
        }
        $s .= $page == $maxpage ? '<li class="mui-active"><a href="#">'.$maxpage.'</a></li>' : '<li><a href="'.$ua[0].$maxpage.$ua[1].'">'.$maxpage.'</a></li>';
        if($page < $maxpage) $s .= '<li class="mui-next"><a class="nextpage" href="'.$ua[0].($page+1).$ua[1].'">'.$lang[1].'</a></li>';
        $s .= '</ul>';
        return $s;
    }

    /**
     * 分页函数 上一页 下一页 格式
     * @param int $page 当前页
     * @param int $maxpage 最大页
     * @param string $url 完整路径
     * @param int $offset 偏移数  无用参数，只是为了 统一
     * @param array $lang 上下页数组
     * @return string
     */
    public static function pages_prev_next($page = 1, $maxpage = 0, $url = '', $offset = 3, $lang = array('上一页', '下一页')){
        if($maxpage < 2) return '';

        $ua = explode('{page}', $url);

        if($page > 1){
            $prev_url = '<a title="'.$lang[0].'" href="'.$ua[0].($page-1).$ua[1].'">'.$lang[0].'</a>';
        }else{
            $prev_url = '';
        }

        if($page < $maxpage){
            $next_url = '<a title="'.$lang[1].'" href="'.$ua[0].($page+1).$ua[1].'">'.$lang[1].'</a>';
        }else{
            $next_url = '';
        }

        $s = '<div class="paginator">'.$prev_url.$next_url.'</div>';
        return $s;
    }

}