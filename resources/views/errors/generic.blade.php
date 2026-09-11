<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Unavailable</title>
    <style>
        @import url('https://fonts.googleapis.com/css?family=Raleway:300,400');
        body {
        background: -webkit-linear-gradient(left, #ffffffb0, #a6a6a6);
        font-family: 'Raleway', sans-serif;
        }
        .logo-image{
        width: 100%;
        display: block;
        text-align: center;
        margin-top: 100px;
        }
        
    </style>
</head>
<body>
    <div class="logo-image">
            <img src="{{ asset('assets/img/skbf_logo.png') }}" 
                        alt="Company Logo" 
                        class="img-fluid shadow-sm p-2 bg-white rounded-3"
                        style="width: 300px; max-width: 100%; height: auto;">
    </div>
    
    <div style="text-align:center;margin-top:100px;">
        <h1>Oops! We couldn't complete your request.</h1>
        <p> We couldn't find or access the page you requested. Please try again later</p>
    </div>
</body>
</html>