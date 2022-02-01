<?php
\defined('_JEXEC') or die;

extract($displayData);

/**
 * Layout variables
 * ---------------------
 * 	$options         : (array)  Optional parameters
 * 	$label           : (string) The html code for the label (not required if $options['hiddenLabel'] is true)
 * 	$input           : (string) The input field html code
 */

$class = empty($options['class']) ? '' : ' ' . $options['class'];
$rel   = empty($options['rel']) ? '' : ' ' . $options['rel'];

$controlsClass = (version_compare(JVERSION, '4', 'lt')) ? 'controlsssss'
	: 'controls';
?>
<div class="control-group<?php echo $class; ?>"<?php echo $rel; ?>>
	<?php if (empty($options['hiddenLabel'])) : ?>
		<div class="control-label"><?php echo $label; ?></div>
	<?php endif; ?>
	<div class="<?php echo $controlsClass; ?>"><?php echo $input; ?></div>
</div>
