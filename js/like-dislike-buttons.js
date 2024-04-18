$(document).ready(function(){
    $(".like-dislike-button").each(function() {
        const button = $(this);
        const comment_id = button.data('commentId');
        
        button.click(function(){
            const action = button.attr('id') === 'like-button' ? 'handle_like' : 'handle_dislike';
            
            $.ajax({
                url: themeData.ajax_url,
                type: 'POST',
                data: {
                    action: action,
                    comment_id: comment_id
                },
                success: function(response) {
                    location.reload();
                },
                error: function(xhr, status, error) {
                    console.log(error)
                }
            });
        });
    });
});
