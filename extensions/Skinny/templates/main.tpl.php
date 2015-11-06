<?php 
$this->insert('head'); 
$this->insert('after:head');
$this->insert('prepend:body');
$this->insert('before:lower-container');
?>
		<div id="lower-container" class="<?php $this->insert('lower-container-class') ?>">
			<?php $this->insert('prepend:lower-container'); ?>
			<?php $this->insert('before:content-container'); ?>
			<div id="content-container" class="container<?php $this->insert('content-container-class') ?>">
				<?php $this->insert('prepend:content-container'); ?>
				<article id="content" class="mw-body-primary <?php $this->insert('content-class') ?>" role="main">
					<a id="top"></a>
					<?php $this->insert('notice'); ?>
					<?php $this->insert('title'); ?>

					
					<div id="bodyContent" class="mw-body">

						<?php 
					/* @todo: Cleanup the following bits; they're basically
										flash messages which show in certain scenarios
										and could be unified into a single template type */ ?>
						<?php $this->insert('tagline'); ?>
						<div id="contentSub"<?php $this->html('userlangattributes') ?>><?php $this->html('subtitle') ?></div>
				<?php if($this->data['undelete']) { ?>
						<div id="contentSub2"><?php $this->html('undelete') ?></div>
				<?php } ?><?php if($this->data['newtalk'] ) { ?>
						<div class="usermessage"><?php $this->html('newtalk')  ?></div>
				<?php } ?><?php if($this->data['showjumplinks']) { ?>
						<div id="jump-to-nav" class="mw-jump"><?php $this->msg('jumpto') ?> <a href="#column-one"><?php $this->msg('jumptonavigation') ?></a><?php $this->msg( 'comma-separator' ) ?><a href="#searchInput"><?php $this->msg('jumptosearch') ?></a></div>
				<?php } ?>


						<!-- start content -->
						<?php $this->insert('content'); ?>


						<?php if($this->data['catlinks']) { $this->html('catlinks'); } ?>
						<!-- end content -->
						<?php if($this->data['dataAfterContent']) { $this->html ('dataAfterContent'); } ?>
						<div class="visualClear"></div>
					</div>
				</article>
				<?php $this->insert('append:content-container'); ?>
			</div><!--/content-container-->
			<?php $this->insert('after:content-container'); ?>
			<?php $this->insert('append:lower-container'); ?>
		</div>
		<?php $this->insert('after:lower-container'); ?>
<?php
$this->insert('before:footer');
$this->insert('footer');
$this->insert('after:footer');
$this->insert('append:body');
echo Html::closeElement( 'body' );
$this->insert('after:body');
echo Html::closeElement( 'html' );
?>