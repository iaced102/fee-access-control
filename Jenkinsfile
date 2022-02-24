pipeline{
    agent any
    stages{
        stage("build"){
            steps{
                withCredentials([usernamePassword(credentialsId: 'docker-hub', passwordVariable: 'DOCKER_REGISTRY_PWD', usernameVariable: 'DOCKER_REGISTRY_USER')]) {
                    sh "docker login -u ${DOCKER_REGISTRY_USER} -p ${DOCKER_REGISTRY_PWD} localhost:5000"
                }
                script {
                    latestTag = sh(returnStdout:  true, script: "git tag --sort=-creatordate | head -n 1").trim()
                    env.BUILD_VERSION = latestTag
                    sh "docker build -t localhost:5000/accesscontrol.symper.vn:${env.BUILD_VERSION} ."
                    sh "docker push localhost:5000/accesscontrol.symper.vn:${env.BUILD_VERSION}"
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