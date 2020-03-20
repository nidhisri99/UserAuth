var url = window.location;
$('ul.nav a').filter(function(){
    return this.href == url;
}).parent().addClass('active').parent().parent().addClass('active');