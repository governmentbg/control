Each script has a comment section describing what it does and if it needs a deamon or cron job.
A quick overview:
 - cache_clean.php - manually purges the cache
 - cache_env.php - moves all config files from and .env file to a .php file
 - deploy.php - makes all needed settings to ensure best security and performance on prod
 - files_clean.php - deletes unused uploaded files
 - install.php - initializes a fresh install (composer will hit this one)
 - tmp_clean.php - cleans unused temporary files
 - migrations.php - applies database migrations
 - passwords_decrypt.php - decrypts all passwords in the database
 - passwords_encrypt.php - encrypts all passwords in the database
 - permissions.php - applies all needed permissions to directories and files
 - semantic.php - fixes semantic form after fomantic-UI upgrade
 - tmp_clean.php - cleans unused temporary files
 - version.php - creates a new version file (developers only)

If there are any script that need to be long running (queue workers, socket servers, etc.) use:
   nohup php -q script.php > /dev/null 2>&1 &