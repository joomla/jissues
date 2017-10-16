Exec {
  path => ['/bin', '/sbin', '/usr/bin', '/usr/sbin'],
}

exec { 'apt-get update':
  path => '/usr/bin',
}

include https

class https {
  package { ['apt-transport-https']:
    ensure  => installed,
  }
}

include jissues

class jissues {
  file { '/var/www/jissues':
    target  => '/vagrant/www',
    ensure  => link,
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

include suryphp

class suryphp {
  exec { 'get-suryphp':
    command => 'wget -O /etc/apt/trusted.gpg.d/suryphp.gpg https://packages.sury.org/php/apt.gpg'
  }

  file { '/etc/apt/sources.list.d/suryphp.list':
    ensure  => present,
    source  => '/vagrant/build/puppet/files/etc/suryphp.list',
    owner => 'root',
    group => 'root';
  }

  exec { 'update-cache':
    command => 'aptitude update',
    require => File['/etc/apt/sources.list.d/suryphp.list']
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

  file { '/etc/apache2/sites-available/jissues.conf':
    ensure  => present,
    require => Package['apache2'],
    source  => '/vagrant/build/puppet/files/apache/jissues.conf';
  }

  exec { 'enable-site':
    require => [Package['apache2'], File['/etc/apache2/sites-available/jissues.conf']],
    before  => Service['apache2'],
    command => 'a2ensite jissues.conf && a2dissite 000-default'
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
    ensure  => running,
    require => Package['mysql-server'],
  }
}

include php

class php {
  package { [
    'php7.1',
    'php7.1-mysql',
    'php7.1-curl',
    'php7.1-xdebug',
    'php7.1-cli',
    'php7.1-intl'
  ]:
    ensure  => installed,
    require => Exec[update-cache]
  }

  file { '/etc/php/7.1/apache2/conf.d/10-mysqli.ini':
    ensure => 'link',
    require => Package['php7.1'],
    target => '/etc/php/7.1/mods-available/mysqli.ini',
    before  => Service['apache2'],
  }

  file { '/etc/php/7.1/apache2/conf.d/98-xdebug.ini':
    ensure  => present,
    require => Package['php7.1'],
    source  => '/vagrant/build/puppet/files/php/xdebug.ini',
    before  => Service['apache2'],
  }

  file { '/etc/php/7.1/apache2/conf.d/99-php.ini':
    ensure  => present,
    require => Package['php7.1'],
    source  => '/vagrant/build/puppet/files/php/php.ini',
    before  => Service['apache2'],
  }
}