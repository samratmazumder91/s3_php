<?php 
namespace SS3;

// error_reporting(-1);
// ini_set('display_errors', 'On');

// require_once 'vendor/autoload.php';
use Aws\Common\Aws;
use Aws\S3\Transfer;

class PocSThree {

	public function __construct($options = array()){
		$this->options = $options;
		$this->aws = Aws::factory('config/aws/config.php');
    	$this->s3Clinet = $this->aws->get('s3');
	}

	/**
	 * lists the buckets
	 */
	public function listS3Buckets(){
		return $this->S3Client->listBuckets();
	}

	/**
	 * list contents of a bucket
	 */
	public function getBucketObjects($bucket_name = ''){
		if(empty($bucket_name) && empty($this->options['bucket_name'])) {
			return false;
		}

		$bucket_name = !empty($bucket_name)? $bucket_name : $this->options['bucket_name'];

		if(empty($bucket_name)) {
			return false;
		}

		$objects = array();

		$iterator = $this->S3Client->getIterator('ListObjects',
			array(
				'Bucket' => $bucket_name
			)
		);

		foreach ($iterator as $object) {
			$objects[] = $object['Key'];
		}

		return $objects;
	}

	/**
	 * to copy a folder from local to s3
	 	$source = local directory absolute path
	 	$destination = s3 bucket
	 */
	public function pushDirectoryToBucket($source, $destination){
		$manager = new Transfer($this->S3Client, $source, $destination);
		$manager->transfer();
	}

	/**
	 * to copy from one bucket to another
	 	$source = the source bucket name
	 	$skey = name of the file to copy
	 	$destination = the destination bucket name
	 	$dkey = name of the file to be copied (can have a name of your choice)
	 */
	public function copyBucketToBucket($source, $skey, $destination, $dkey){

		return $this->S3Client->copyObject(array(
			'Bucket' => $destination,
			'Key' => $dkey,
			'CopySource' => "{$source}/{$skey}"
		));
	}

	/**
	 * to delete a bucket
	 */
	public function deleteBucket($bucket_name){
		return $this->S3Client->deleteBucket(array(
			'Bucket' => $bucket_name
		));
	}

	/**
	 * to create a new bucket
	 	$bucket_name = bucket name
	 	$acl = access control list. e.g. 'private|public-read|public-read-write|authenticated-read'
	 */
	public function createBucket($bucket_name, $acl = ''){
		if($acl == ''){
			$acl = 'public-read';
		}

		return $this->S3Client->createBucket(array(
			'ACL' => $acl,
			'Bucket' => $bucket_name
		));
	}

	/**
	 * to delete object from bucket
	 	$bucket_name = bucket name
	 	$key = the name of the object to be deleted
	 */
	public function deleteObject($bucket_name, $key){
		return $this->S3Client->deleteObject(array(
			'Bucket' => $bucket_name,
			'Key' => $key
		));
	}

	/**
	 * to delete multiple objects from bucket
	 	$bucket_name = bucket name
	 	$objects = array containing the name of the objects to be deleted
	 */
	public function deleteObjects($bucket_name, $objects = array()){

		if($bucket_name == '' || empty($objects)){
			return false;
		}

		//create the required array
		$toDelete = array();
		foreach ($objects as $value) {
			$toDelete['Objects'][] = array('Key' => $value);			
		}
		return $this->S3Client->deleteObjects(array(
			'Bucket' => $bucket_name,
			'Delete' => $toDelete
		));
	}

	/**
	 * to download image from remote url to s3 bucket
	 	$bucket_name = destination bucket name
	 	$source_url = url from which image/file is to be downloaded
	 	$key = destination file name
	 	$folder = folder structure inside which you want the image to be place
	 	E.g. $folder = test/testing/  if it is to be placed inside testing folder which is inside test folder
	 	Note: Keep it blank if you need it to store in the bucket itself
	 	also end it with a /
	 */
	public function putInBucketFromUrl($bucket_name, $source_url, $key, $folder){

		$WWW_ROOT = '/var/www/flat_public/app/webroot/';
		$local_path = $WWW_ROOT.'tmp_folder/'.$key;
		$local_img = file_put_contents($local_path, file_get_contents($source_url));

		if($local_img){
			return $this->S3Client->putObject(array(
				'Bucket' => $bucket_name,
				'SourceFile' => $local_path,
				'Key' => $folder.$key
			));
		}

		unlink($local_path);
	}

	/**
	 * to create folder inside a bucket
	 	$bucket_name = destination bucket name
	 	$folder_name = desired folder name
	 */
	public function createFolder($bucket_name, $folder_name){
		return $this->S3Client->putObject(array(
				'Bucket' => $bucket_name,
				'Body' => "",
				'Key' => $folder_name.'/'
			));
	}

	/**
	 * to upload file to Bucket or folder inside bucket
	 	$bucket_name = destination bucket name
	 	$source_file = absolute path of the file
	 	$key = destination file name

	 	INCASE you want to store the file inside a folder in the bucket,
	 	$key = folder(s)/file_name
	 	E.g. : test/sam.jpg
	 */
	public function uploadFileToBucket($bucket_name, $source_file, $key){
		return $this->S3Client->putObject(array(
			'Bucket' => $bucket_name,
			'SourceFile' => $source_file,
			'Key' => $key
		));
	}

}


/**
 * use the following in your application where you need
 */
// $options = array(
// 	'version'     => 'latest',
// 	'region'      => 'us-east-1',
// 	'bucket_name' => '',
// 	'credentials' => array(
// 		'key' => '',
// 		'secret' => ''
// 	)
// );


// $poc = new PocSThree($options);
// print_r($poc);