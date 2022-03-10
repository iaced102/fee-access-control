pipeline{
    agent any
    environment{
        SERVICE_NAME = "accesscontrol.symper.vn"
        BRANCH_NAME = "${GIT_BRANCH.split("/")[1]}"
    }
    stages{
        stage("test") {
            steps{
                sh "echo ${BRANCH_NAME}"
            }
        }
        // stage("build"){
        //     steps{
        //         withCredentials([usernamePassword(credentialsId: 'docker-hub', passwordVariable: 'DOCKER_REGISTRY_PWD', usernameVariable: 'DOCKER_REGISTRY_USER')]) {
        //             sh 'echo $DOCKER_REGISTRY_PWD | docker login -u $DOCKER_REGISTRY_USER --password-stdin localhost:5000'
        //         }
        //         script {
        //             latestTag = sh(returnStdout:  true, script: "git tag --sort=-creatordate | head -n 1").trim()
        //             env.BUILD_VERSION = latestTag
        //             sh "docker build -t localhost:5000/${SERVICE_NAME}:${env.BUILD_VERSION} ."
        //             sh "docker push localhost:5000/${SERVICE_NAME}:${env.BUILD_VERSION}"
        //             sh "docker image rm localhost:5000/${SERVICE_NAME}:${env.BUILD_VERSION}"
        //         }
        //     }
        // }
        // stage("deploy to k8s"){
        //     steps{
        //         sh "chmod +x changeTag.sh"
        //         sh "./changeTag.sh ${env.BUILD_VERSION}"
        //         sshagent(['ssh-remote']) {
        //             sh "scp -o StrictHostKeyChecking=no n8n/* root@103.148.57.32:/root/kubernetes/deployment/inter/${SERVICE_NAME}"
        //             sh "ssh root@103.148.57.32 rm -rf /root/kubernetes/deployment/inter/${SERVICE_NAME}/php_deployment.yaml"
        //             sh "ssh root@103.148.57.32 kubectl apply -f /root/kubernetes/deployment/inter/${SERVICE_NAME}"
        //         }
        //     }
        // }
    }
    post{
        // always{
        //     emailext body: '$PROJECT_NAME - Build # $BUILD_NUMBER - $BUILD_STATUS: \nCheck console output at $BUILD_URL to view the results.',
        //     subject: '$PROJECT_NAME - Build # $BUILD_NUMBER - $BUILD_STATUS!',
        //     to: 'hoangnd@symper.vn'
        // }
        success{
            echo "========pipeline executed successfully ========"
        }
        failure{
            echo "========pipeline execution failed========"
        }
    }
}