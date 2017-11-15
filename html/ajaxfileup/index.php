<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>fileUP</title>
</head>
<body>
<form id="formImg" action="./up.php"  method="post"  target="hidden_frame" enctype="multipart/form-data"
      style="margin: 0px;padding: 0px;" >
    <table class="table-detail" cellpadding="0" cellspacing="0" border="0" type="main">
        <tbody>
        <tr>
            <th width="15%">头像: </th>
            <td colspan="3">
                <div class="control-group">
                    <label class="control-label">缩略图:</label>
                    <div class="controls">
                        <div class="fileupload fileupload-new" data-provides="fileupload">
                            <div class="fileupload-new thumbnail" style="width: 200px; height: 150px;">
                                <img id="img_url"
                                     src="" alt="" />
                            </div>
                            <div class="fileupload-preview fileupload-exists thumbnail" style="max-width: 200px; max-height: 150px; line-height: 20px;"></div>
                            <div>
                                <span class="btn btn-file"><span class="fileupload-new">选择图片</span>
                                <input type="file" class="default" name="img" onchange="uploadImg()"/>
                            </div>
                        </div>
                        <span class="label label-important">注意!</span>
                        <span>
                        图片大小控制在150KB以内
                    </span>
                    </div>
                </div>
                <iframe style="display:none" name='hidden_frame' id="hidden_frame"></iframe>

            </td>
        </tr>
        </tbody>
    </table>
</form>
</body>
</html>
<script type="text/javascript" src="./jquery-1.9.1.min.js"></script>
<script>
//    $(function(){
//
//    })
	function uploadImg()
	{
		$("#formImg").submit();
	}

	function callback(message)
	{
		var paths=message.split("/");
		$("#img_url").attr("src",message);
	}

</script>

