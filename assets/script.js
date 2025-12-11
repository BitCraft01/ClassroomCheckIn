$(document).ready(function(){

    $("#loginForm").submit(function(e){
        e.preventDefault();

        let email = $("#email").val();
        let password = $("#password").val();

        // Regex validation n
        let emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!emailRegex.test(email)){
            $("#response").text("Invalid email format.");
            return;
        }

        $.ajax({
            url: "process/login.php",
            type: "POST",
            data: { email: email, password: password },
            success: function(response){
                if (response === "success") {
                    window.location.href = "main.php";
                } else {
                    $("#response").text(response);
                }
            }
        });
    });

});