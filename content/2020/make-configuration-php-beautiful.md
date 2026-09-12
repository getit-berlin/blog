## make configuration.php beautiful

von gRoot

am 2020-09-02

in IT-Services, Entwicklung

Joomla has a quite ugly configuration file. configuration.php is cluttered and sorted i a very strange way?

```
<?php
class JConfig {
	public $offline = '1';
	public $offline_message = 'Wartungsmodus';
	public $display_offline_message = '1';
	public $offline_image = '';
	public $sitename = 'joomlavel-joomla-dev';
	public $editor = 'tinymce';
	public $captcha = '0';
	public $list_limit = '20';
	public $access = '1';
	public $debug = '0';
	...
	...
	...
	public $feed_email = 'none';
	public $log_path = '/logs';
	public $tmp_path = '/tmp';
	public $lifetime = '15';
	public $session_handler = 'database';
	public $shared_session = '0';
}
```

I really like https://12factor.net/ and its twelve-factor app approach. [configuration recommendation](https://12factor.net/config) is to seperate config in a way that they are  language- and OS-agnostic. But how to do this? Here is our approach:

```
    public function __construct(){
		if (file_exists(SELF::JoomLavelApiDirectory.'.env')) {
			$env = parse_ini_file (SELF::JoomLavelApiDirectory.'.env',false, INI_SCANNER_RAW);
			$this->sitename = $env['APP_NAME'];
			$this->debug = (int)filter_var($env['APP_DEBUG'], FILTER_VALIDATE_BOOLEAN);;
			$this->live_site = $env['APP_URL'];
			$this->dbtype = $env['DB_CONNECTION'].'i';
			$this->host = $env['DB_HOST'];
			$this->db = $env['DB_DATABASE'];
			$this->user = $env['DB_USERNAME'];
			$this->password = $env['DB_PASSWORD'];

			$this->mailfrom = $env['MAIL_FROM_ADDRESS'];
			$this->fromname = $env['MAIL_FROM_NAME'];
			$this->smtpport = $env['MAIL_PORT'];
			$this->smtphost = $env['MAIL_HOST'];
			$this->smtpuser = $env['MAIL_USERNAME'];
			$this->smtppass = $env['MAIL_PASSWORD'];
		}

    }
```

The configuration.php could also be seperated in blocks:

```
	#APP
	public $live_site = '';
	public $sitename = "joomla";

	#DB
	public $dbtype = 'mysqli';
	public $host = 'localhost';
	public $user = 'groot';
	public $password = '';
	public $db = 'joomlavel-dev';
	public $dbprefix = 'f862e_';

	#SESSION
	public $session_handler = 'database';
	public $shared_session = '0';
	public $lifetime = '15';
```

You can see the files in our [Github::JoomLavel](https://github.com/JoomLavel/) project.

tag configuration.php git Github joomla joomlavel php