jQuery(document).ready(function(){
	
	
	var isDateInputSupported = function(){
		var elem = document.createElement('input');
		elem.setAttribute('type','date');
		elem.value = 'foo';
		return (elem.type == 'date' && elem.value != 'foo');
	}
	
	if (!isDateInputSupported()){

		Date.format = 'yyyy-mm-dd'; // MySQL Data format
		jQuery('input.date').datePicker({startDate: '2011-01-01'});
		
		jQuery('.ui-datepicker').hide();
	
	} else{
		jQuery('.date-instruction').hide();
	}
	
});
