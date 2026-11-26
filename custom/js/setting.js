$(document).ready(function() {
	// main menu
	$("#navSetting").addClass('active');
	// sub manin
	$("#topNavSetting").addClass('active');
	// change username
	$("#changeUsernameForm").unbind('submit').bind('submit', function() {
		var form = $(this);
		var username = $("#username").val();
		if(username == "") {
			$("#username").after('<p class="text-danger">El campo de nombre de usuario es obligatorio.</p>');
			$("#username").closest('.form-group').addClass('has-error');
		}else{
			$(".text-danger").remove();
			$('.form-group').removeClass('has-error');
			$("#changeUsernameBtn").button('loading');
			$.ajax({
				url: form.attr('action'),
				type: form.attr('method'),
				data: form.serialize(),
				dataType: 'json',
				success:function(response) {
					$("#changeUsernameBtn").button('reset');
					// remove text-error 
					$(".text-danger").remove();
					// remove from-group error
					$(".form-group").removeClass('has-error').removeClass('has-success');
					if(response.success == true){																						
						// shows a successful message after operation
						$('.changeUsenrameMessages').html(
							'<div class="alert alert-success">'+
	            				'<button type="button" class="close" data-dismiss="alert">&times;</button>'+
	            				'<strong><i class="glyphicon glyphicon-ok-sign"></i></strong> '+ response.messages +
	          				'</div>'
	          			);
						// remove the mesages
	          			$(".alert-success").delay(500).show(10, function() {
							$(this).delay(3000).hide(10, function() {
								$(this).remove();
							});
						}); // /.alert	          					
					}else{
						// shows a successful message after operation
						$('.changeUsenrameMessages').html(
							'<div class="alert alert-warning">'+
	            				'<button type="button" class="close" data-dismiss="alert">&times;</button>'+
	            				'<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> '+ response.messages +
	          				'</div>'
	          			);
						// remove the mesages
	          			$(".alert-warning").delay(500).show(10, function(){
							$(this).delay(3000).hide(10, function(){
								$(this).remove();
							});
						}); // /.alert	          					
					}
				} // /success 
			}); // /ajax
		}
		return false;
	});

	// Toggle password visibility
	$(document).on('click', '.toggle-password-visibility', function(e) {
		e.preventDefault();
		var targetSelector = $(this).data('target');
		var input = $(targetSelector);
		var icon = $(this).find('i');
		if (input.attr('type') === 'password') {
			input.attr('type', 'text');
			icon.removeClass('glyphicon-eye-open').addClass('glyphicon-eye-close');
		} else {
			input.attr('type', 'password');
			icon.removeClass('glyphicon-eye-close').addClass('glyphicon-eye-open');
		}
	});

	// Dynamic password strength evaluation
	function evaluateStrength(p) {
		if (!p) return { score: 0, percent: 0, label: '', cssClass: 'label-default', barClass: '' };
		var rules = {
			minLength: p.length >= 8,
			hasUpper: /[A-Z]/.test(p),
			hasLower: /[a-z]/.test(p),
			hasNumber: /[0-9]/.test(p),
			hasSpecial: /[^A-Za-z0-9]/.test(p)
		};
		var score = 0;
		if (rules.minLength) score++;
		if (rules.hasUpper) score++;
		if (rules.hasLower) score++;
		if (rules.hasNumber) score++;
		if (rules.hasSpecial) score++;

		if (score <= 1) {
			return { score: score, percent: 20, label: 'Muy Débil', cssClass: 'label-danger', barClass: 'progress-bar-danger' };
		} else if (score === 2) {
			return { score: score, percent: 40, label: 'Débil', cssClass: 'label-warning', barClass: 'progress-bar-warning' };
		} else if (score === 3) {
			return { score: score, percent: 60, label: 'Aceptable', cssClass: 'label-info', barClass: 'progress-bar-info' };
		} else if (score === 4) {
			return { score: score, percent: 80, label: 'Fuerte', cssClass: 'label-primary', barClass: 'progress-bar-primary' };
		} else {
			return { score: score, percent: 100, label: 'Muy Segura', cssClass: 'label-success', barClass: 'progress-bar-success' };
		}
	}

	function checkPasswordMatch() {
		var npass = $("#npassword").val();
		var cpass = $("#cpassword").val();
		var matchEl = $("#password-match-status");

		if (cpass.length > 0) {
			if (npass === cpass && npass.length >= 8) {
				matchEl.html('<span class="text-success"><i class="glyphicon glyphicon-ok"></i> Las contraseñas coinciden</span>');
			} else {
				matchEl.html('<span class="text-danger"><i class="glyphicon glyphicon-remove"></i> Las contraseñas no coinciden</span>');
			}
		} else {
			matchEl.empty();
		}
	}

	$("#npassword").on('input keyup', function() {
		var val = $(this).val();
		if (val.length > 0) {
			var res = evaluateStrength(val);
			$("#password-strength-container").show();
			$("#password-strength-label").text(res.label).removeClass('label-default label-danger label-warning label-info label-primary label-success').addClass(res.cssClass);
			$("#password-strength-bar").css('width', res.percent + '%').removeClass('progress-bar-danger progress-bar-warning progress-bar-info progress-bar-primary progress-bar-success').addClass(res.barClass);
		} else {
			$("#password-strength-container").hide();
		}
		checkPasswordMatch();
	});

	$("#cpassword").on('input keyup', function() {
		checkPasswordMatch();
	});

	$("#changePasswordForm").unbind('submit').bind('submit', function() {
		var form = $(this);
		$(".text-danger").remove();
		var currentPassword = $("#password").val();
		var newPassword = $("#npassword").val();
		var conformPassword = $("#cpassword").val();

		var hasError = false;

		if(currentPassword == "") {
			$("#password").closest('.input-group').after('<p class="text-danger">El campo contraseña actual es obligatorio</p>');
			$("#password").closest('.form-group').addClass('has-error');
			hasError = true;
		} else {
			$("#password").closest('.form-group').removeClass('has-error');
		}

		if(newPassword == "") {
			$("#npassword").closest('.input-group').after('<p class="text-danger">Se requiere el campo nueva contraseña</p>');
			$("#npassword").closest('.form-group').addClass('has-error');
			hasError = true;
		} else if (newPassword.length < 8) {
			$("#npassword").closest('.input-group').after('<p class="text-danger">La nueva contraseña debe tener al menos 8 caracteres</p>');
			$("#npassword").closest('.form-group').addClass('has-error');
			hasError = true;
		} else {
			$("#npassword").closest('.form-group').removeClass('has-error');
		}

		if(conformPassword == "") {
			$("#cpassword").closest('.input-group').after('<p class="text-danger">El campo confirmar contraseña es necesario</p>');
			$("#cpassword").closest('.form-group').addClass('has-error');
			hasError = true;
		} else if (newPassword !== conformPassword) {
			$("#cpassword").closest('.input-group').after('<p class="text-danger">La confirmación no coincide con la nueva contraseña</p>');
			$("#cpassword").closest('.form-group').addClass('has-error');
			hasError = true;
		} else {
			$("#cpassword").closest('.form-group').removeClass('has-error');
		}

		if(hasError) {
			return false;
		} else {
			$(".form-group").removeClass('has-error');
			$(".text-danger").remove();

			$.ajax({
				url: form.attr('action'),
				type: form.attr('method'),
				data: form.serialize(),
				dataType: 'json',
				success:function(response) {
					console.log(response);
					if(response.success == true) {
						form[0].reset();
						$("#password-strength-container").hide();
						$("#password-match-status").empty();
						$('.changePasswordMessages').html(
							'<div class="alert alert-success">'+
	            				'<button type="button" class="close" data-dismiss="alert">&times;</button>'+
	            				'<strong><i class="glyphicon glyphicon-ok-sign"></i></strong> '+ response.messages +
	          				'</div>'
	          			);
						// remove the mesages
	          			$(".alert-success").delay(500).show(10, function(){
							$(this).delay(3000).hide(10, function(){
								$(this).remove();
							});
						}); // /.alert	    
					}else{

						$('.changePasswordMessages').html(
							'<div class="alert alert-warning">'+
	            				'<button type="button" class="close" data-dismiss="alert">&times;</button>'+
	            				'<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> '+ response.messages +
	          				'</div>'
	          			);
						// remove the mesages
	          			$(".alert-warning").delay(500).show(10, function(){
							$(this).delay(3000).hide(10, function(){
								$(this).remove();
							});
						}); // /.alert	          	
					}
				} // /success function
			}); // /ajax function
		} // /else
		return false;
	});

	// Spending limit form submission
	$("#spendingLimitForm").unbind('submit').bind('submit', function(e) {
		e.preventDefault();
		var form = $(this);
		var btn = $("#saveSpendingLimitBtn");
		btn.prop('disabled', true);

		$.ajax({
			url: form.attr('action'),
			type: form.attr('method'),
			data: form.serialize(),
			dataType: 'json',
			success: function(response) {
				btn.prop('disabled', false);
				if(response.success == true) {
					$('.spendingLimitMessages').html(
						'<div class="alert alert-success alert-dismissible" role="alert">'+
							'<button type="button" class="close" data-dismiss="alert">&times;</button>'+
							'<strong><i class="glyphicon glyphicon-ok-sign"></i></strong> ' + response.messages +
						'</div>'
					);
					setTimeout(function() {
						window.location.reload();
					}, 1200);
				} else {
					$('.spendingLimitMessages').html(
						'<div class="alert alert-danger alert-dismissible" role="alert">'+
							'<button type="button" class="close" data-dismiss="alert">&times;</button>'+
							'<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> ' + response.messages +
						'</div>'
					);
				}
			},
			error: function() {
				btn.prop('disabled', false);
				$('.spendingLimitMessages').html(
					'<div class="alert alert-danger alert-dismissible" role="alert">'+
						'<button type="button" class="close" data-dismiss="alert">&times;</button>'+
						'<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> Error de comunicación con el servidor.'+
					'</div>'
				);
			}
		});
		return false;
	});
}); // /document