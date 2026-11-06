<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/app/database.php';
try{database()->exec(file_get_contents(dirname(__DIR__).'/database/chat.sql'));echo "Private follow-up chat tables are ready. Existing records were preserved.\n";}
catch(PDOException $e){fwrite(STDERR,"Chat setup failed. Check the database connection and permissions.\n");exit(1);}
