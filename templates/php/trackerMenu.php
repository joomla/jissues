<?php
/*
{# Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved. #}
{# GNU General Public License version 2 or later; see LICENSE.txt#}
*/
?>
<ul class="nav menu nav-pills">
	<?php $parts = explode('/', $this->uri->route) ?>
	<li class="<?php echo "" == $this->uri->route || "tracker" == $parts[0] ? "active" : "" ?>">
		<a href="<?php echo $this->uri->base->path ?>tracker">Tracker</a>
	</li>
	<li class="<?php echo "projects" == $this->uri->route || "project" == $parts[0] ? "active" : "" ?>">
		<a href="<?php echo $this->uri->base->path ?>projects">Projects</a>
	</li>
	<li class="<?php echo "users" == $this->uri->route || "user" == $parts[0] ? "active" : "" ?>">
		<a href="<?php echo $this->uri->base->path ?>users">Users</a>
	</li>
	<li class="<?php echo "markdownpreview" == $this->uri->route ? "active" : "" ?>">
		<a href="<?php echo $this->uri->base->path ?>markdownpreview">MDPreview</a>
	</li>

	<?php if ($this->user->isAdmin) : ?>
		<li class="<?php echo "config" == $this->uri->route ? "active" : "" ?>">
			<a href="<?php echo $this->uri->base->path ?>config">Config</a>
		</li>
	<?php endif ?>

	<?php if ($this->user->id) : ?>
		<li class="dropdown pull-right">
			<a class="dropdown-toggle btn btn-small" data-toggle="dropdown"
			   href="javascript:;"><?php echo $this->user->username ?>
				<span class="caret"></span>
			</a>
			<ul class="dropdown-menu">
				<li><a href="<?php echo $this->uri->base->path ?>user">Profile</a></li>
				<li class="divider"><span></span></li>
				<li><a href="<?php echo $this->uri->base->path ?>logout">Logout </a>
				</li>
			</ul>
		</li>
	<?php else : ?>
		<li class="pull-right">
			<a class="btn btn-success btn-small login" href="<?php echo $this->loginUrl ?>">Login with GitHub</a>
		</li>
	<?php endif ?>
</ul>
