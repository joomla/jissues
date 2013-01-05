<?php
class JConfig
{
	/*
	 * Enter your GitHub account credentials for the $github** params
	 * When prompted to authenticate to GitHub, these values will be used
	 */
	public $github_user		= '';
	public $github_password	= '';
	public $dbtype			= 'mysqli';
	public $host			= 'localhost';
	public $user			= '';
	public $password		= '';
	public $db				= '';
	public $dbprefix		= 'jos_';
	public $ftp_host		= '127.0.0.1';
	public $ftp_port		= '21';
	public $ftp_user		= '';
	public $ftp_pass		= '';
	public $ftp_root		= '';
	public $ftp_enable		= 0;
	public $tmp_path		= '/tmp';
	public $log_path		= '/var/logs';
	public $mailer			= 'mail';
	public $mailfrom		= 'admin@localhost.home';
	public $fromname		= '';
	public $sendmail		= '/usr/sbin/sendmail';
	public $smtpauth		= '0';
	public $smtpsecure		= 'none';
	public $smtpport		= '25';
	public $smtpuser		= '';
	public $smtppass		= '';
	public $smtphost		= 'localhost';
	public $debug			= 0;
	public $caching			= '0';
	public $cachetime		= '15';
	public $cache_handler	= 'file';
	public $language		= 'en-GB';
	public $secret			= null;
	public $editor			= 'none';
	public $offset			= 'UTC';
	public $sess_lifetime	= 15;
	public $sess_handler	= 'database';
}
