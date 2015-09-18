<?php
namespace Topxia\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Topxia\WebBundle\Util\UploadToken;
use Topxia\Common\FileToolkit;
use Topxia\Common\ArrayToolkit;
use Topxia\WebBundle\Util\UploaderToken;

/**
 * 素材库上传组件控制器
 */
class UploaderController extends BaseController
{

    public function initAction(Request $request)
    {
        $token = $request->query->get('token');
        $parser = new UploaderToken();
        $params = $parser->parse($token);
        if (!$params) {
        	return $this->createJsonResponse(array('error' => '上传授权码不正确，请重试！'));
        }

        $params = array_merge($request->request->all(), $params);

        $params['uploadCallback'] = $this->generateUrl('uploader_upload_callback', array(), true);
        $params['processCallback'] = $this->generateUrl('uploader_process_callback', array(), true);

        $result = $this->getUploadFileService()->initUpload($params);

        $result['uploadProxyUrl'] = $this->generateUrl('uploader_entry');

        return $this->createJsonResponse($result);
    }

    public function finishedAction(Request $request)
    {
        $params = $request->request->all();
        $this->getUploadFileService()->finishedUpload($params);
        return $this->createJsonResponse(true);
    }

    public function uploadCallbackAction(Request $request)
    {
        $params = $request->request->all();
        return $this->createJsonResponse(true);
    }

    public function processCallbackAction(Request $request)
    {
        $params = $request->request->all();

        $this->getUploadFileService()->setFileProcessed($params);

        return $this->createJsonResponse(true);
    }

    public function batchUploadAction(Request $request)
    {
        $token = $request->query->get('token');
        $parser = new UploaderToken();
        $params = $parser->parse($token);
        if (!$params) {
            return $this->createJsonResponse(array('error' => '上传授权码不正确，请重试！'));
        }

        return $this->render('TopxiaWebBundle:Uploader:batch-upload-modal.html.twig', array(
            'token' => $token,
            'targetType' => $params['targetType'],
        ));
    }

    public function entryAction(Request $request)
    {
        return new Response('-_-');
    }

    public function chunksStartAction(Request $request)
    {
        $headers = array(
            'Upload-Token: ' . $request->headers->get('Upload-Token'),
        );

        $params = $request->request->all();

        $url = $this->setting('developer.cloud_file_server', '') . '/chunks/start';

        $result = $this->_post($url, $params, $headers);

        return new Response($result);

    }

    public function chunksFinishAction(Request $request)
    {
        $headers = array(
            'Upload-Token: ' . $request->headers->get('Upload-Token'),
        );

        $params = $request->request->all();

        $url = $this->setting('developer.cloud_file_server', '') . '/chunks/finish';

        $result = $this->_post($url, $params, $headers);

        return new Response($result);
    }

    protected function _post($url, $params, $headers)
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, 1);

        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_URL, $url);

        $response = curl_exec($curl);
        $curlinfo = curl_getinfo($curl);
        $header = substr($response, 0, $curlinfo['header_size']);
        $body = substr($response, $curlinfo['header_size']);

        curl_close($curl);

        $context = array(
            'CURLINFO' => $curlinfo,
            'HEADER' => $header,
            'BODY' => $body,
        );

        return $body;
    }


    protected function getUploadFileService()
    {
        return $this->getServiceKernel()->createService('File.UploadFileService2');
    }


}

