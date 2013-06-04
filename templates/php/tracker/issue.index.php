<?php
/*
{# Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved. #}
{# GNU General Public License version 2 or later; see LICENSE.txt #}

{% extends "index.twig" %}

{% block title %}{{ project.title }} #{{ item.issue_number }}{% endblock %}

{% block headerText %}{{ project.title }}{% endblock %}

{% block content %}
*/
?>
<h2><?= $this->project['title'] ?></h2>

<h3>#{{ item.issue_number }}: {{ item.title }}</h3>

<div class="well well-small">
	<strong>Description</strong>

	<p>
		{{ item.description|raw }}
	</p>
</div>

<hr/>

<b>Just for ref.:</b>

<ul class="unstyled">
	{% for name, value in item %}
	<li><b>{{ name }}</b>: {{ value }}</li>
	{% endfor %}
</ul>

{{ dump(item) }}

{% endblock %}
