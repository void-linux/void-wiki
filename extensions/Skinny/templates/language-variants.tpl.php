<?php if( !empty($variants) ):?>
<div class="language-variants">
<h4><?php $this->msg('otherlanguages') ?></h4>
<ul>
<?php foreach($variants as $k => $lang): ?>
<?php echo $this->makeListItem($key, $langlink); ?>
<?php endforeach; ?>	
</ul>
</div>
<?php endif; ?>