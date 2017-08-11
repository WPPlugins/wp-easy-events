jQuery(document).ready(function() {
$=jQuery;
var $captcha_container = $('.captcha-container');
if ($captcha_container.length > 0) {
        var $image = $('img', $captcha_container),
        $anchor = $('a', $captcha_container);
        $anchor.bind('click', function(e) {
                e.preventDefault();
                $image.attr('src', $image.attr('src').replace(/nocache=[0-9]+/, 'nocache=' + +new Date()));
        });
}
$.validator.setDefaults({
    ignore: [],
});
$.extend($.validator.messages,event_attendee_vars.validate_msg);
function createRadioCheckBox(ele, i)
{
  if(ele.attr('type') == 'checkbox')
  {          
     var newID = "cbx-"+ ele.attr('id') + i;
     var iconNameOn = 'ui-icon-check';
  }
  else if(ele.attr('type') == 'radio')
  {
     var newID = "rd-"+ ele.attr('id') +i;
     var iconNameOn = '';
  }
  ele.attr({ "id": newID  })
     .prop({ "type": ele.attr('type') })
     .after($("<label />").attr({ for: newID  }))
     .button({ text: false, icons: {
        primary: ele[0].checked ? iconNameOn: ""
        }
     })
     .change(function(e) {
        if(ele.attr('type') == 'radio')
        {
           $('label.radio span').removeClass( iconNameOn + ' ui-icon');
        }
        var toConsole = $(this).button("option", {
          icons: {
            primary: $(this)[0].checked ? iconNameOn : ""
          }
        });      
     });
     return ele;
}
$('label.checkbox label').removeClass('ui-corner-all');
$.validator.addMethod('uniqueAttr',function(val,element){
  var unique = true;
  var data_input = $("form").serialize();
  $.ajax({
    type: 'GET',
    url: event_attendee_vars.ajax_url,
    cache: false,
    async: false,
    data: {action:'emd_check_unique',data_input:data_input, ptype:'emd_event_attendee',myapp:'wp_easy_events'},
    success: function(response)
    {
      unique = response;
    },
  });
  return unique;                
}, event_attendee_vars.unique_msg);
$('#event_attendee').validate({
onfocusout: false,
onkeyup: false,
onclick: false,
errorClass: 'text-danger',
rules: {
  emd_attendee_first_name:{
},
emd_attendee_last_name:{
},
emd_attendee_quantity:{
min : 1,
integer : true,
},
emd_attendee_email:{
email  : true,
},
},
success: function(label) {
label.remove();
},
errorPlacement: function(error, element) {
if (typeof(element.parent().attr("class")) != "undefined" && element.parent().attr("class").search(/date|time/) != -1) {
error.insertAfter(element.parent().parent());
}
else if(element.attr("class").search("radio") != -1){
error.insertAfter(element.parent().parent());
}
else if(element.attr("class").search("select2-offscreen") != -1){
error.insertAfter(element.parent().parent());
}
else if(element.attr("class").search("selectpicker") != -1 && element.parent().parent().attr("class").search("form-group") == -1){
error.insertAfter(element.parent().find('.bootstrap-select').parent());
} 
else if(element.parent().parent().attr("class").search("pure-g") != -1){
error.insertAfter(element);
}
else {
error.insertAfter(element.parent());
}
},
});
$(document).on('click','#singlebutton_event_attendee',function(event){
     var form_id = $(this).closest('form').attr('id');
     
     $.each(event_attendee_vars.event_attendee.req, function (ind, val){
         if(!$('input[name='+val+'],#'+ val).closest('.row').is(":hidden")){
             $('input[name='+val+'],#'+ val).rules("add","required"); 
         }
     });
     var valid = $('#' + form_id).valid();
     if(!valid) {
        event.preventDefault();
        return false;
     }
     event.preventDefault();
     form_data = $('#'+form_id+' :input').serialize();
     nonce_val = $('#event_attendee_nonce').val();
     $.ajax({
        type: 'POST',
        url:event_attendee_vars.ajax_url ,
        data: {action:'wp_easy_events_submit_ajax_form',form_name:form_id,nonce:nonce_val,vals:form_data},
        success: function(msg) {
            $('#'+form_id+'-success-error').html(msg);
            $('#'+form_id+'-success-error').show();
            new_pos = $('#'+form_id+'-success-error').offset();
window.scrollTo(new_pos.left,new_pos.top);
$('#event_attendee').hide();
 
            
        }
    });
});
});
