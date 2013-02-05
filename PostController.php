<?php

class PostController extends Controller
{
  /**
   * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
   * using two-column layout. See 'protected/views/layouts/column2.php'.
   */
  public $layout='//layouts/column2';

  public function __construct($id,$module=null) {
    parent::__construct($id,$module);
    if (Yii::app()->user->getId() === null) {
      $this->layout='//layouts/column1';
    }
  }
  /**
   * @return array action filters
   */
  public function filters()
  {
    return array(
        'accessControl', // perform access control for CRUD operations
        'postOnly + delete', // we only allow deletion via POST request
    );
  }

  /**
   * Specifies the access control rules.
   * This method is used by the 'accessControl' filter.
   * @return array access control rules
   */
  public function accessRules()
  {
    $accessRules = array (
        array('allow', // allow authenticated user to perform 'create' and 'update' actions
            'actions'=>array('index','view'),
            'users'=>array('@'),
        ),
        array('allow', // allow authenticated user to perform 'create' and 'update' actions
            'actions'=>array('index'),
            'users'=>array('?'),
        ),
    );
    if( $this->getAction()->getId() == 'view') {
      $post = $this->loadModel($_GET['id']);
      // allow unauthenticated user to view post if it is published
      $accessRules += array(array('allow',
          'actions'=>array('index','view'),
          'users'=>array('?'),
          'expression'=> $post->status.' == 1',
      ));
    }

    $accessRules += array(
        array('allow', // allow admin user to perform 'admin' and 'delete' actions
            'actions'=>array('create','update','delete'),
            'users'=>array('admin'),
        ),
        array('deny',  // deny all users
            'users'=>array('*'),
        ),
    );
    return $accessRules;
  }

  /**
   * Displays a particular model.
   * @param integer $id the ID of the model to be displayed
   */
  public function actionView($id)
  {
    $post = $this->loadModel($id);
    $comment = $this->newComment($post);

    $this->render('view',array(
        'model'=>$post,
        'comment'=>$comment,
    ));
  }

  /**
   * Creates a new model.
   * If creation is successful, the browser will be redirected to the 'view' page.
   */
  public function actionCreate()
  {
    $model=new Post;

    // Uncomment the following line if AJAX validation is needed
    // $this->performAjaxValidation($model);

    if(isset($_POST['Post']))
    {
      $model->attributes=$_POST['Post'];
      if($model->save())
        $this->redirect(array('view','id'=>$model->pid));
    }

    $this->render('create',array(
        'model'=>$model,
    ));
  }

  /**
   * Updates a particular model.
   * If update is successful, the browser will be redirected to the 'view' page.
   * @param integer $id the ID of the model to be updated
   */
  public function actionUpdate($id)
  {
    $model=$this->loadModel($id);

    // Uncomment the following line if AJAX validation is needed
    // $this->performAjaxValidation($model);

    if(isset($_POST['Post']))
    {
      $model->attributes=$_POST['Post'];
      //$model->setTags($_POST['tags']);
       
      if($model->save())
        $this->redirect(array('view','id'=>$model->pid));
    }

    $this->render('update',array(
        'model'=>$model,
    ));
  }

  /**
   * Deletes a particular model.
   * If deletion is successful, the browser will be redirected to the 'admin' page.
   * @param integer $id the ID of the model to be deleted
   */
  public function actionDelete($id)
  {
    $this->loadModel($id)->delete();

    // if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
    if(!isset($_GET['ajax']))
      $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
  }

  /**
   * Lists all models.
   */
  public function actionIndex()
  {
    //Add condition to filter out posts, that cannot be viewed by user
    $options = array();
    if (Yii::app()->user->isGuest) {
      $options += array(
          'criteria' => array(
              'condition' => 'status=1',
          )
      );
    }

    $dataProvider=new CActiveDataProvider('Post', $options);
    $this->render('index',array(
        'dataProvider'=>$dataProvider,
    ));
  }

  /**
   * Manages all models.
   */
  public function actionAdmin()
  {
    $model=new Post('search');
    $model->unsetAttributes();  // clear any default values
    if(isset($_GET['Post']))
      $model->attributes=$_GET['Post'];

    $this->render('admin',array(
        'model'=>$model,
    ));
  }

  /**
   * Returns the data model based on the primary key given in the GET variable.
   * If the data model is not found, an HTTP exception will be raised.
   * @param integer the ID of the model to be loaded
   */
  public function loadModel($id)
  {
    $model=Post::model()->findByPk($id);
    if($model===null)
      throw new CHttpException(404,'The requested page does not exist.');
    return $model;
  }

  /**
   * Performs the AJAX validation.
   * @param CModel the model to be validated
   */
  protected function performAjaxValidation($model)
  {
    if(isset($_POST['ajax']) && $_POST['ajax']==='post-form')
    {
      echo CActiveForm::validate($model);
      Yii::app()->end();
    }
  }

  /**
   * Creates a new comment.
   * This method attempts to create a new comment based on the user input.
   * If the comment is successfully created, the browser will be redirected
   * to show the created comment.
   * @param Post the post that the new comment belongs to
   * @return Comment the comment instance
   */
  protected function newComment($post)
  {
    $comment=new Comment;
    if(isset($_POST['ajax']) && $_POST['ajax']==='comment-form')
    {
      echo CActiveForm::validate($comment);
      Yii::app()->end();
    }
    if(isset($_POST['Comment']))
    {
      $comment->attributes=$_POST['Comment'];
      if($post->addComment($comment))
      {
        //if($comment->status==Comment::STATUS_PENDING)
        Yii::app()->user->setFlash('commentSubmitted','Thank you for your comment. Your comment will be posted once it is approved.');
        $this->refresh();
      }
    }
    return $comment;
  }

  /** Get related tags in list view, separated by commma.
   *
   * @param Post $post
   * @return Post's tags string.
   */
  protected function getTags ($post) {
    foreach ($post->tagsref as $tag){

    }
    //return implode(' ,', );
  }

  /**
   * Allows to print object.
   * @return string
   */
  public function tagsToString($model) {
    if ($tags = $model->tags){
      $return = array_shift($tags)->title;
      foreach ($tags as $tag) {
        $return .= ', '.$tag->title;
      }
      return $return;
    }
  }
}
