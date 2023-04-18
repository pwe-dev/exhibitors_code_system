console.log("działa js");

jQuery( document ).ready( function( $ ) {
     // GET PARTNERS CHECKBOXES
     partners_array = new Array();
     $('#table-exhibitor').find('tbody td:first-child').each(function(){
         var context = $(this).text();
         partners_array.push(context);            
     });
     // GET CLEAN AND NO REPEAT PARTNERS CHECKBOXES
     var uniquePartners = new Array();
     $.each(partners_array, function(i, el){
         if($.inArray(el, uniquePartners) === -1) uniquePartners.push(el);
     });
     // PRINT CHECKBOX OR UNIQUE PARTNERS
     $.each(uniquePartners, function(index, val){
         var all_checkboxes =  "<label><input class='checkboxes' name='"+val.toUpperCase().trim()+"' value='"+val.toUpperCase().trim()+"' type='checkbox' checked>"+val.trim()+"</label>";
         $('#checkboxes').append(all_checkboxes);
     });
     // LET'START FILTERING
     $(".checkboxes, #myInput").bind("keyup change", function(e) {
         // DECLARE FILTERS
         var imputFilter = $( "#myInput" ).val().toUpperCase().trim();
         var checkbox_filter = new Array();
         // ADD CHECKBOXES VALUE TO PARTNERS ARRAY
         $('.checkboxes').each(function(){
             if(this.checked){
                 checkbox_filter.push($(this).val());
             }                       
         });
         // LOOP THROUGH THE TABLE
         $("table tr").each(function (index) {
             if (!index) return;
             // DECLARE IN FIRST ROW CHECKED PARTNERS
             var current_td_partner = $(this).find("td:first-child");
             var id_partner = current_td_partner.text().toString().toUpperCase().trim();
             current_td_partner = current_td_partner.parent();
             // DECLARE IN THIRD ROW TYPED COMPANY
             var current_td_company = $(this).find("td:nth-child(3)");
             var id_company = current_td_company.text().toString().toUpperCase().trim();
             current_td_company = current_td_company.parent();
             // SHOW OR HIDE COLUMN IN DEPENDENCY OF FILTERS
             if(checkbox_filter.indexOf(id_partner) > -1 && id_company.indexOf(imputFilter) > -1 ){
                     current_td_partner.show();
             }else {
                     current_td_partner.hide();
             }   
         });
     });
    
} );