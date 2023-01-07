pipeline{
    agent any
    environment{
        SERVICE_NAME = "accesscontrol.symper.vn"
        // SERVICE_ENV = "prod"
        KAFKA_SUBCRIBE = true
        Author_ID=sh(script: "git show -s --pretty=%an", returnStdout: true).trim()
        Author_Name=sh(script: "git show -s --pretty=%ae", returnStdout: true).trim()
    }
    stages{
        stage ("quality control") {
            when {
                expression {
                    return env.BRANCH_NAME != 'master';
                }
            }
            environment {
                SERVICE_ENV = "test"
                DB_HOST = "14.225.0.166"
            }
            stages {
                stage("deploy to k8s") {
                    when {
                        expression {
                            "${Author_Name}"?.startsWith("release")
                        }
                    }
                    steps{
                        withCredentials([
                            usernamePassword(credentialsId: 'dev_database', passwordVariable: 'POSTGRES_PASS', usernameVariable: 'POSTGRES_USER')
                            usernamePassword(credentialsId: 'qc_test_user_pass', passwordVariable: 'USER_PASS', usernameVariable: 'USER_NAME')
                        ]) {
                            sh "chmod +x changeTag.sh"
                            sh './changeTag.sh $SERVICE_NAME:2.6.22 $SERVICE_ENV $POSTGRES_USER $POSTGRES_PASS $DB_HOST'
                            sshagent(['qc_test_ssh_key']) {
                                sh "ssh -o StrictHostKeyChecking=no $USER_NAME@14.225.36.157 'echo $USER_PASS | sudo -S rm -rf /root/kubernetes/deployment/${SERVICE_ENV}/${SERVICE_NAME}'"
                                sh "ssh -o StrictHostKeyChecking=no $USER_NAME@14.225.36.157 'echo $USER_PASS | sudo -S  mkdir -p /root/kubernetes/deployment/${SERVICE_ENV}/${SERVICE_NAME}'"
                                sh "ssh -o StrictHostKeyChecking=no $USER_NAME@14.225.36.157 'mkdir -p /tmp/${SERVICE_ENV}/${SERVICE_NAME}'"
                                sh "scp -o StrictHostKeyChecking=no k8s/* $USER_NAME@14.225.36.157:/tmp/${SERVICE_ENV}/${SERVICE_NAME}"
                                sh "ssh -o StrictHostKeyChecking=no $USER_NAME@14.225.36.157 'echo $USER_PASS | sudo -S  mv /tmp/${SERVICE_ENV}/${SERVICE_NAME}/* /root/kubernetes/deployment/${SERVICE_ENV}/${SERVICE_NAME}'"
                                sh "ssh -o StrictHostKeyChecking=no $USER_NAME@14.225.36.157 'echo $USER_PASS | sudo -S  kubectl config set-context --current --namespace=${SERVICE_ENV}'"
                                sh "ssh -o StrictHostKeyChecking=no $USER_NAME@14.225.36.157 'echo $USER_PASS | sudo -S  kubectl apply -f /root/kubernetes/deployment/${SERVICE_ENV}/${SERVICE_NAME}'"
                            }
                        }
                    }
                }
            }
        }
        
        stage ("production") {
            when {
                branch = "master"
            }
            environment {
                SERVICE_ENV = "prod"
                DB_HOST = "103.56.157.180"
            }
            stages {
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
                //     when {
                //         expression {
                //             "${Author_Name}"?.startsWith("release")
                //         }
                //     }
                //     steps{
                //         withCredentials([usernamePassword(credentialsId: 'accesscontrol_database', passwordVariable: 'POSTGRES_PASS', usernameVariable: 'POSTGRES_USER')]) {
                //             sh "chmod +x changeTag.sh"
                //             sh './changeTag.sh $SERVICE_NAME:$BUILD_VERSION $SERVICE_ENV $POSTGRES_USER $POSTGRES_PASS'
                //             sshagent(['ssh-remote']) {
                //                 sh "ssh root@103.148.57.32 rm -rf /root/kubernetes/deployment/${SERVICE_ENV}/${SERVICE_NAME}"
                //                 sh "ssh root@103.148.57.32 mkdir /root/kubernetes/deployment/${SERVICE_ENV}/${SERVICE_NAME}"
                //                 sh "scp -o StrictHostKeyChecking=no k8s/* root@103.148.57.32:/root/kubernetes/deployment/${SERVICE_ENV}/${SERVICE_NAME}"
                //                 sh "ssh root@103.148.57.32 kubectl config set-context --current --namespace=${SERVICE_ENV}"
                //                 sh "ssh root@103.148.57.32 kubectl apply -f /root/kubernetes/deployment/${SERVICE_ENV}/${SERVICE_NAME}"
                //             }
                //         }
                //     }
                // }
                // stage("triggerkafka") {
                //     steps {
                //         script {
                //             if ("${KAFKA_SUBCRIBE}".toBoolean() == true) {
                //                 sh 'curl -s -I -X GET https://${SERVICE_NAME}/KafkaService/subscribe | grep HTTP/ | awk \'{print "Code: "  $2}\''
                //             } else{
                //                 echo 'None Kafka subscriber'
                //             }
                //         }
                //     }
                // }
            }
        }
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