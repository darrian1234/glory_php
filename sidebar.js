// Toggle sidebar
$('#sidebar-toggle').click(function() {
    $('.sidebar').toggleClass('collapsed');
    $('.main-content').toggleClass('ml-64');
});

// Auto-expand sidebar on larger screens
function handleSidebar() {
    if (window.innerWidth < 768) {
        $('.sidebar').addClass('collapsed');
        $('.main-content').removeClass('ml-64');
    } else {
        $('.sidebar').removeClass('collapsed');
        $('.main-content').addClass('ml-64');
    }
}

// Run on load and resize
$(document).ready(handleSidebar);
$(window).resize(handleSidebar);