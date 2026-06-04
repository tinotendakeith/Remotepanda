<?php

namespace App\Controllers;

use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\API\ResponseTrait;
use Config\Services;
use LasseRafn\InitialAvatarGenerator\InitialAvatar;

class Auth extends BaseController
{

    use ResponseTrait;

    public function login()
    {
        helper(["form", 'html']);

        //if form has been submitted process it
        if (isset($_POST['submit'])) {

            $rules = [
                'login' => 'required',
                'password' => 'required',
                "stay-signed-in" => "if_exist|in_list[on,off]"
            ];

            if ($this->validate($rules)) {

                $username = $this->request->getPost('login');
                $password = $this->request->getPost('password');
                $staySignedIn = $this->request->getPost('stay-signed-in');

                $user = get_user($username);

                if ($user !== false) {
                    if ($user->verifyPassword($password)) {
                        $session = session();

                        $expire = $staySignedIn === "on" ? DAY * 2 : HOUR * 6;
                        $session->setTempdata('user_id', $user->id, $expire);

                        return redirect()->to('dashboard');
                    } else {
                        $this->validator->setError('login', 'Wrong username, email or password.');
                    }
                } else {
                    $this->validator->setError('login', 'Username or email not found.');
                }
            }
        }

        $validation = $this->validator;

        return view('login', compact('validation'));
    }

    /**
     * Generate image for initials
     *
     * @since   1.0.0
     * @version 1.0.0
     *
     * @author  Richard Muvirimi <rich4rdmuvirimi@gmail.com>
     */
    public function avatar()
    {

        $rules = [
            'name' => 'required',
            'background' => 'required|hex|max_length[6]',
            "color" => "required|hex|max_length[6]",
            'rounded' => "if_exist|in_list[true,false]",
            'size' => "if_exist|numeric",
            'height' => "if_exist|numeric",
            'width' => "if_exist|numeric",
            'length' => "if_exist|numeric",
            'fontSize' => "if_exist|numeric",
        ];

        if ($this->validate($rules)) {

            $image = new InitialAvatar();
            $image->font('./fonts/OpenSans-Semibold.ttf');

            $params = array_keys($rules);

            foreach ($params as $param) {
                $data = $this->request->getGet($param) ?? "";
                if (strlen($data) != 0) {
                    $image->{$param}($data);

                    if ($param == 'rounded' && $data) {
                        $image->smooth();
                    }
                }
            }

            $this->response->setContentType('image/png');

            $options = [
                'max-age' => WEEK,
                's-maxage' => WEEK,
                'etag' => $image->getInitials(),
            ];
            $this->response->setCache($options);

            echo $image->generate()->stream('png', 100);
        } else {
            $response = $this->validator->getErrors();

            return $this->failValidationErrors($response);
        }
    }

    public function logout(): RedirectResponse
    {
        Services::session()->destroy();

        return redirect()->to("/login");
    }
}
