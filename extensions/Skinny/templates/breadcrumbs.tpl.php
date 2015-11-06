<nav class="breadcrumbs" id="main-breadcrumbs">
<?php foreach($trees as $tree): ?>
	<ul>
	<?php foreach($tree as $cat): ?>
		<li><?php echo $cat; ?>
	<?php endforeach; ?>
	</ul>
<?php endforeach; ?>
</nav>