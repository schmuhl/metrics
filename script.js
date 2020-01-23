



// connect to the metric recording endpoint and pass the data along
function addData ( metric, date, value ) {
  var jqxhr = $.getJSON("record.php?metric="+metric+"&date="+date+"&value="+value)
    .done(function(data) {
      if ( data.code === 0 ) {
        addMessage(data.message);
      } else {
        addMessage('Could not add data: server responded, "'+data.message+'"');
      }
    }).fail(function() {
      addMessage('Failed to add data: server communication error.');
    });
  return true;
}


// show a message to the user
function addMessage ( message ) {
  // make sure that there is a place to put the message!
  if ( !$('UL#messages').length ) {
    $('#messagesBox').html('<ul id="messages"></ul>');
  }
  $('UL#messages').append('<li>'+message+'</li>');
}
