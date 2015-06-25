<?php include('_tabbar.php'); ?>
<ul id="typing-list"></ul>
<section class="drawers">
	<section class="drawer users">
		<ul>
		</ul>
	</section>
	<section class="drawer stati">
		<ul>
			<li><a href="javascript:void(0);" data-status="available" title="Verfügbar">&#xf00c;</a></li>
			<li><a href="javascript:void(0);" data-status="do_not_disturb" title="Nicht stören">&#xf056;</a></li>
			<li><a href="javascript:void(0);" data-status="on_the_phone" title="Am Telefon">&#xf095;</a></li>
		</ul>
	</section>
	<section class="drawer files">
		<ul>
		</ul>
	</section>
	<section class="drawer links">
		<ul>
		</ul>
	</section>
</section>
<section class="timeline container"></section>
<section class="talkbox">
	<section class="container">
		<form action="" method="" class="">
			<textarea></textarea>
			<input class="clearfix" type="submit" value="&#x23CE; abschicken">
			<span class="button fileinput-button">
				<i class="icon-upload-alt"></i>
				<span>datei hochladen</span>
				<input id="fileupload" type="file" name="files[]" data-url="/file/upload/">
			</span>
		</form>
	</section>
</section>
