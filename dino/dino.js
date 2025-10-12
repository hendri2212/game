var original = Runner.prototype.gameOver; 
Runner.prototype.gameOver = function () {};  // Disable game over logic

// Membuat Dino melompat secara otomatis setiap interval tertentu
var interval = setInterval(function() {
    if (Runner.instance_ && Runner.instance_.tRex && !Runner.instance_.gameOver) {
        Runner.instance_.tRex.startJump();
    }
}, 100);  // Mengatur interval untuk kontrol kecepatan lompatan