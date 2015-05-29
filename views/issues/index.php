<div class='container'>
	<ul class='breadcrumb'>
		<li><a href='./'>Home</a></li>
		<li class='active'>Bug Issues and Reports</li>
	</ul>

	<div class='page-header'>
		<h2>
			<strong>Bug Issues and Reports</strong>
		</h2>
	</div>

	<div class="list-group">

		<?php if (count($issues)): ?>

			<?php foreach($issues as $issue): ?>

				<div class="list-group-item"><?php echo ucwords($issue->getTitle()); ?></div>
				
			<?php endforeach; ?>

		<?php endif; ?>

	</div>
</div>