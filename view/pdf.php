<!DOCTYPE html>
<html>

<head>
    <style type="text/css">
        html,
        body {
            position: relative;
        }
        body {
            color: #000000;
            font-family:'Source Sans Pro', sans-serif;
            font-size: 14px;
            background-color: #ffffff;
        }

        hr {
            color: green;
        }
        .row {
            clear: both;
        }
        .heading-pdf {
            font-family:'Source Sans Pro', sans-serif;
            font-size: 20px;
            padding: 0;
            margin: 0;
        }
        .row-pdf {
            padding-top: 20px;
            padding-bottom: 20px;
        }

        .item-image {
            display: block;
            margin-bottom: 20px;
        }

        .item-image img {
            width: 100%;
        }
</style>
</head>
<body>

<main>
    <div class="row row-pdf">
        <div class="grid-container">
            <div class="grid-x grid-padding-x">

                <div class="large-12 cell">
                    <p><b>Name:</b> <?php echo $data['sender-name']; ?></p>
                    <p><b>Contact Email:</b> <?php echo $data['sender-email']; ?></p>
                </div>

                <div class="large-12 cell">
                    <hr>
                </div>

                <div class="large-12 cell">
                     <p><b>General Description of Problem/Concern/Fault:</b></p>
                     <p><?php echo $data['sender-description']; ?></p>
                </div>

                <div class="large-12 cell">
                    <hr>
                </div>

                 <div class="large-12 cell">
                     <p><b>Image(s) attached:</b> <?php if (count($files) > 0) { echo 'Yes'; } else { echo 'No'; } ?></p>
                </div>

            </div>
        </div>
    </div>

    <div class="row row-attachments">

        <?php
        // Attachments
        if (count($files) > 0) {
            foreach ($files as $file) {
                $imagePath = $this->uploaddir . basename($file['name']);
                // $imagePath = 'http://lorempixel.com/400/200/';
        ?>
            <div class="item-image">
                <img src="<?php echo $imagePath;?>" alt="dummy"/>
                <p><?php echo basename($file['name']); ?></p>
            </div>
        <?php
            }
        }
        ?>

    </div>
</main>


</body>
</html>
