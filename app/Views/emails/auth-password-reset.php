<!DOCTYPE html>
<html>
<head>
  <title>corenominal</title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="color-scheme" content="light dark">
  <meta name="supported-color-schemes" content="light dark">
  <style>
    body, table, td {
      font-family: Arial, sans-serif;
      background-color: #FFFFFF;
      color: #000000;
    }
    img {
      border: none;
      display: block;
      max-width: 100%;
    }
    @media only screen and (max-width: 600px) {
      .container {
        width: 100% !important;
      }
    }
  </style>
</head>
<body style="margin:0; padding:0; background-color:#FFFFFF; color:#000000;">
  <center>
    <table width="100%" bgcolor="#FFFFFF" cellpadding="0" cellspacing="0" border="0">
      <tr>
        <td align="center" bgcolor="#FFFFFF">
          <!-- Container -->
          <table width="600" cellpadding="0" cellspacing="0" border="0" class="container" style="width:600px; max-width:100%; background-color:#FFFFFF;" bgcolor="#FFFFFF">

            <!-- Hero Image (with VML fallback for Outlook) -->
            <tr>
              <td align="center">
                <!--[if gte mso 9]>
                <v:rect xmlns:v="urn:schemas-microsoft-com:vml" fill="true" stroke="false" style="width:600px;height:160px;">
                  <v:fill type="frame" src="<?= site_url() ?>email-header-600x160.png" color="#000000" />
                  <v:textbox inset="0,0,0,0">
                <![endif]-->
                  <div>
                    <img src="<?= site_url() ?>email-header-600x160.png" width="600" height="160" alt="" style="display:block; width:100%; height:auto;">
                  </div>
                <!--[if gte mso 9]>
                  </v:textbox>
                </v:rect>
                <![endif]-->
              </td>
            </tr>

            <!-- Body -->
            <tr>
              <td style="padding: 30px 20px 30px 20px; color:#000000; background-color:#FFFFFF; font-size:16px; line-height:1.6;" bgcolor="#FFFFFF">
                <h2 style="margin-top:0; color:#000000;">Hello</h2>
                <p style="color:#000000;">A password reset has been requested for your account. Click the button below to set a new password.</p>
                <p style="color:#000000;">This link will expire in <strong>1 hour</strong>. If you did not request a password reset, you can safely ignore this email &mdash; your password will not change.</p>
              </td>
            </tr>

            <!-- CTA Button -->
            <tr>
                <td align="center" style="padding: 0px 20px 40px 20px; background-color:#FFFFFF;" bgcolor="#FFFFFF">
                    <!--[if mso]>
                    <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word"
                    href="<?= site_url('auth/password-reset/confirm/' . $reset_uuid) ?>" style="height:48px;v-text-anchor:middle;width:200px;" arcsize="10%"
                    stroke="f" fillcolor="#000000">
                    <v:fill type="gradient" color="#000000" color2="#000000"/>
                    <w:anchorlock/>
                    <center style="color:#FFFFFF;font-family:sans-serif;font-size:16px;font-weight:bold;">
                        Reset Password
                    </center>
                    </v:roundrect>
                    <![endif]-->
                    <!--[if !mso]>-->
                    <a href="<?= site_url('auth/password-reset/confirm/' . $reset_uuid) ?>"
                    style="background-color:#000000; background: linear-gradient(#000000, #000000); color:#FFFFFF; display:inline-block; font-family:sans-serif; font-size:16px; font-weight:bold; line-height:48px; text-align:center; text-decoration:none; width:200px; -webkit-text-size-adjust:none; border-radius:5px;">
                    Reset Password
                    </a>
                    <!--<![endif]-->
                </td>
            </tr>

            <!-- Fallback link -->
            <tr>
              <td style="padding: 0px 20px 30px 20px; color:#000000; background-color:#FFFFFF; font-size:13px; line-height:1.6;" bgcolor="#FFFFFF">
                <p style="color:#666666;">If the button above does not work, copy and paste the following link into your browser:</p>
                <p style="word-break:break-all;"><a href="<?= site_url('auth/password-reset/confirm/' . $reset_uuid) ?>" style="color:#000000;"><?= site_url('auth/password-reset/confirm/' . $reset_uuid) ?></a></p>
              </td>
            </tr>

            <!-- Footer -->
            <tr>
              <td style="padding: 30px; background-color:#FFFFFF; font-size:12px; text-align:center; color:#000000;" bgcolor="#FFFFFF">
                &copy; <?= date('Y') ?> <a style="color: #000000; text-decoration: underline;" href="<?= site_url() ?>">corenominal</a>. All rights reserved.
                <br>
                The information contained in this message and any attachments is intended for the named recipients only. It may contain privileged and confidential information. If you are not the addressee or the person responsible for delivering this to the addressee, you may not copy, distribute or take action in reliance on it. If you have received this message in error would you please notify me immediately and delete and destroy all that has been sent to you.
              </td>
            </tr>

          </table>
        </td>
      </tr>
    </table>
  </center>
</body>
</html>
