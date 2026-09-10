<?php
file_put_contents('/tmp/xadd_dbg.log', date('H:i:s').' set_cms_content_data id='.var_export($id,true).' data_keys='.implode(',', array_keys((array)$data)).' table='.var_export($table,true).' r='.var_export($r,true).PHP_EOL, FILE_APPEND);
