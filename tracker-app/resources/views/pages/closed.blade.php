<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Troop Tracker &mdash; Temporarily Closed</title>
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/bootswatch/5.2.3/darkly/bootstrap.min.css" />
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" />
</head>
<body class="bg-black d-flex align-items-center justify-content-center min-vh-100">
    <div class="text-center px-4" style="max-width: 480px;">
        <i class="fa fa-lock fa-4x text-danger mb-4"></i>
        <h1 class="text-white mb-3">Troop Tracker is Currently Closed</h1>
        @if(!empty($message))
            <p class="text-secondary fs-5">{{ $message }}</p>
        @endif
        <p class="text-muted mt-4">Please refer to command staff for additional information.</p>
    </div>
</body>
</html>
