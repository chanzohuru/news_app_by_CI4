<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Models\UsersModel;
use App\Models\PostsModel;
use Config\Services;

class Posts extends BaseController
{
   use ResponseTrait;

   

   public function index()
   {
      $PostsModel = new PostsModel();

      $date = date("Y-m-d");
      $dataz = $PostsModel->like('published_at', $date)->find();

      if (count($dataz) == "0")
      {

         $url = "http://api.mediastack.com/v1/news?access_key=f32591403d2b1dc28ff8769ad5f63a27&languages=en&countries=us&date=$date";
         //echo $url;

         $curl = curl_init();
         curl_setopt_array($curl, array(
         CURLOPT_URL => $url,
         CURLOPT_RETURNTRANSFER => true,
         CURLOPT_ENCODING => '',
         CURLOPT_MAXREDIRS => 10,
         CURLOPT_TIMEOUT => 0,
         CURLOPT_FOLLOWLOCATION => true,
         CURLOPT_SSL_VERIFYPEER =>  TRUE,
         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
         CURLOPT_CUSTOMREQUEST => 'GET',
         ));

         $response = curl_exec($curl);
         $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
         $errmsg  = curl_error( $curl );

         curl_close($curl);

         $res = json_decode($response, true);

         foreach($res['data'] as $vl)
         {
            $data = [
               'author' => $vl['author'],
               'title' => $vl['title'],
               'description' => $vl['description'],
               'url' => $vl['url'],
               'source' => $vl['source'],
               'image' => $vl['image'],
               'category' => $vl['category'],
               'published_at' => $vl['published_at']
            ];

            //echo "<pre>" . print_r($data) . "</pre>";

            $PostsModel->insert($data);
            //echo $PostsModel->getLastQuery() . "<br>";
         }

         $dataz = $PostsModel->like('published_at', $date)->find();
      }

      return $this->respond([
         'status' => 1,
         'data' => $dataz
      ], 200);
   
   }

   public function compile($id=0)
   {
      $UsersModel = new UsersModel();

      if ($id > 0)
      {
         $users = $UsersModel->where('id', $id)->find();
      }
      else
      {
         $users = $UsersModel->findAll();
      }

      return $users;
   }

   public function add_user()
   {
      $rules =[
         'firstname' => 'required',
         'lastname' => 'required',
         'email' => 'required|valid_email|is_unique[users.email]',
         'password' => 'required',
         'dob' => 'required'
      ];
      $val = $this->validate($rules);

      if (!$val)
      {
         return $this->respond([
            'status' => 0,
            'message' => Services::validation()->getErrors()
         ], 400);
      }
      else
      {
         $data = [
            'firstname' => $this->request->getVar('firstname'),
            'lastname'  => $this->request->getVar('lastname'),
            'email'     => $this->request->getVar('email'),
            'password'  => $this->request->getVar('password'),
            'dob'       => $this->request->getVar('dob')
         ];

         $UsersModel = new UsersModel();
         $UsersModel->insert($data);
         $id = $UsersModel->insertID;

         $data = $this->compile($id);

         return $this->respond([
            'status' => 1,
            'message' => "User created successfully",
            'data' => $data
         ], 201);
      }
   }

   public function edit_user($id=0)
   {
      if ($id == 0)
      {
         return $this->respond([
            'status' => 0,
            'message' => "id missing"
         ], 400);
      }
      else
      {
         $rules =[
            'firstname' => 'required',
            'lastname' => 'required',
            'email' => 'required|valid_email',
            'password' => 'required',
            'dob' => 'required'
         ];
         $val = $this->validate($rules);
   
         if (!$val)
         {
            return $this->respond([
               'status' => 0,
               'message' => Services::validation()->getErrors()
            ], 400);
         }
         else
         {
            $UsersModel = new UsersModel();

            $data = [
               'firstname' => $this->request->getVar('firstname'),
               'lastname'  => $this->request->getVar('lastname'),
               'email'     => $this->request->getVar('email'),
               'password'  => $this->request->getVar('password'),
               'dob'       => $this->request->getVar('dob'),
            ];


            $UsersModel->update($id, $data);
            
            $data = $this->compile($id);

            return $this->respond([
               'status' => 1,
               'message' => "User updated successfully",
               'data' => $data
            ], 200);
         }
      }
   }


   public function delete_user($id=0)
   {
      if ($id == 0)
      {
         return $this->respond([
            'status' => 0,
            'message' => "id missing"
         ], 400);
      }
      else
      {
         $data = $this->compile($id);

         $UsersModel = new UsersModel();

         $UsersModel->delete($id);

         return $this->respond([
            'status' => 1,
            'message' => "User deleted successfully",
            'data' => $data
         ], 200);
      }
   }
}
