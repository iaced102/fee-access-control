pipeline{
    agent any
    stages{
        stage("build"){
            steps{
                withCredentials([usernamePassword(credentialsId: 'docker-hub', passwordVariable: 'DOCKER_REGISTRY_PWD', usernameVariable: 'DOCKER_REGISTRY_USER')]) {
                    // assumes Jib is configured to use the environment variables
                    sh 'echo $DOCKER_REGISTRY_PWD'
                    sh 'echo $DOCKER_REGISTRY_USER'
                    sh 'echo $DOCKER_PASSWORD'
                    sh 'echo $DOCKER_USERNAME'
                }
                // script {
                //     latestTag = sh(returnStdout:  true, script: "git tag --sort=-creatordate | head -n 1").trim()
                //     env.BUILD_VERSION = latestTag
                //     sh "docker build -t localhost:5000/accesscontrol.symper.vn:${env.BUILD_VERSION} ."
                //     sh "docker push localhost:5000/accesscontrol.symper.vn:${env.BUILD_VERSION}"
                // }
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