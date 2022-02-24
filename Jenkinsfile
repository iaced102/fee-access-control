pipeline{
    agent any
    stages{
        stage("build"){
            steps{
                script {
                    latestTag = sh(returnStdout:  true, script: "git tag --sort=-creatordate | head -n 1").trim()
                    env.BUILD_VERSION = latestTag
                    echo "env-BUILD_VERSION"
                    echo "${env.BUILD_VERSION}"
                }
            }
        }
        stage("test"){
            steps{
                echo "test"
            }
        }
        stage("deploy"){
            steps{
                echo "deploy"
            }
        }
    }
    post{
        always{
            echo "========always========"
        }
        success{
            echo "========pipeline executed successfully ========"
        }
        failure{
            echo "========pipeline execution failed========"
        }
    }
}