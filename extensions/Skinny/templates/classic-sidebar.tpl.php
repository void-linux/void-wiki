<?php $this->before('mediawiki-sidebar') ?>
		<nav id="mediawiki-sidebar">
		<?php 
		$this->prepend('mediawiki-sidebar');

		foreach($sections as $name => $section): 
			if(!empty($section)):	?>
			<div class="sidebar-section">
				<h4><?php $this->msg($name) ?></h4>
				<ul class="nav">
				<?php foreach($section as $key => $item): ?>
				<?php echo $this->makeListItem($key, $item); ?>
				<?php endforeach; ?>
				</ul>
			</div>
		<?php 
			endif;
		endforeach;

		wfRunHooks( 'SkinTemplateToolboxEnd', array( &$this, true ) );

		$this->append('mediawiki-sidebar');
		?>
		</nav>
<?php $this->after('mediawiki-sidebar') ?>