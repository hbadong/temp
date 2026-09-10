<?php
file_put_contents('/tmp/xadd_dbg.log', date('H:i:s').' set_cms_content_views id='.var_export($id,true).' data='.json_encode($data).' table='.var_export($table,true).' r='.var_export($r,true).PHP_EOL, FILE_APPEND);
