Exec {
	path => ['/bin', '/sbin', '/usr/bin', '/usr/sbin'],
}

exec { 'apt-get update':
	path => '/usr/bin',
}

include jissues

class jissues {
	file { '/var/www/jissues':
		target  => '/vagrant/www',
		ensure  => 'link',
		require => Package['apache2'],
		notify  => Service['apache2'],
	}
}

include system

class system {
	package { ['curl', 'gettext']:
		ensure  => installed,
		require => Exec['apt-get update'],
	}
	file { '/etc/environment':
		ensure  => present,
		source  => '/vagrant/build/puppet/files/etc/environment',
		owner => 'root',
		group => 'root';
	}
}

include apache

class apache {
	package { 'apache2':
		name    => 'apache2-mpm-prefork',
		ensure  => installed,
		require => Exec['apt-get update'],
	}

	service { 'apache2':
		ensure  => running,
		require => Package['apache2'],
	}

	exec { 'enable-mod_rewrite':
		require => Package['apache2'],
		before  => Service['apache2'],
		command => '/usr/sbin/a2enmod rewrite'
	}

	file { '/etc/apache2/sites-available/jissues.virt':
		ensure  => present,
		require => Package['apache2'],
		source  => '/vagrant/build/puppet/files/apache/jissues.virt';
	}

	exec { 'enable-site':
		require => [Package['apache2'], File['/etc/apache2/sites-available/jissues.virt']],
		before  => Service['apache2'],
		command => 'a2ensite jissues.virt && a2dissite 000-default'
	}
}

include mysql

class mysql {
	package {
		['mysql-server', 'mysql-client']:
		ensure  => installed,
		require => Exec['apt-get update'],
	}

	service { 'mysql':
		ensure  => 'running',
		require => Package['mysql-server'],
	}
}

include php

class php {
	package { [
		'php5',
		'php5-mysql',
		'php5-curl',
		'php5-xdebug',
		'php5-cli'
	]:
		ensure  => 'installed',
		require => Exec['apt-get update'],
	}

	file { '/etc/php5/conf.d/21-xdebug.ini':
		ensure  => present,
		require => Package['php5'],
		source  => '/vagrant/build/puppet/files/php/21-xdebug.ini';
	}

	file { '/etc/php5/conf.d/666-php.ini':
		ensure  => present,
		require => Package['php5'],
		source  => '/vagrant/build/puppet/files/php/666-php.ini';
	}
}
