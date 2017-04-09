function begen(post_id,user_id){
	jQuery.ajax({
		url: my_ajax_url.ajax_url,
		type: 'post',
		dataType: 'json',
		data: {
			action : 'begen_birak',
			post_id: post_id,
			user_id: user_id
		},
		success: function(x){
			if(!x.hata){
				if(x.tok==1){
					$("span.like").addClass("active");
					$("span.like i").addClass("fa-heart");
					$("span.like i").removeClass("fa-heart-o");
				}else{
					$("span.like").removeClass("active");
					$("span.like i").removeClass("fa-heart");
					$("span.like i").addClass("fa-heart-o");
				}
			}else{
				alert(x.hata);
			}
		}
	});
}
