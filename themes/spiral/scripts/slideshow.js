$(document).ready(function(){
	$('.slideshow').click(function(){
		$('.slideshow').fadeOut("slow", function(){
			$('.slideshow').fadeIn("slow");
		});
		return false;
	});
});
