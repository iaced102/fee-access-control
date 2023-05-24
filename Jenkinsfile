pipeline{
    agent any
    environment{
        SERVICE_NAME = "accesscontrol.symper.vn"
        HOST_DOMAIN = "accesscontrol-forward.symper.vn"
        KAFKA_SUBCRIBE = true
        APP_NAME = sh (script: "echo $SERVICE_NAME | cut -d'.' -f1", returnStdout: true).trim()
        AUTHOR_NAME = sh(script: "git show -s --pretty=%an", returnStdout: true).trim()
        BRANCH_NAME = "${GIT_BRANCH.split('/').size() > 1 ? GIT_BRANCH.split('/')[1..-1].join('/') : GIT_BRANCH}"
        COMMIT_MESSAGE = sh(script: "git log --format=%B -n 1", returnStdout: true).trim()
        MSTEAMS_WEBHOOK=credentials('ms_teams_webhook')
    }
    stages{
        stage ("quality control") {
            when {
                expression {
                    return AUTHOR_NAME == "devsymper" && BRANCH_NAME == "dev"
                }
            }
            environment {
                SERVICE_ENV = "test"
                // BUILD_VERSION = "2.6.80"
                SSH_HOST = "10.20.166.15"
                POSTGRES_HOST = "10.20.166.52"
                POSTGRES_DB = "accesscontrol_symper_vn"
                CLICKHOUSE_HOST = "10.20.166.52"
                CACHE_HOST = "redis-server.redis-server.svc.cluster.local"
                KAFKA_PREFIX = "10.20.166.6"
            }
            stages {
                stage("build"){
                    steps{
                        withCredentials([usernamePassword(credentialsId: 'docker-hub', passwordVariable: 'DOCKER_REGISTRY_PWD', usernameVariable: 'DOCKER_REGISTRY_USER')]) {
                            sh 'echo $DOCKER_REGISTRY_PWD | docker login -u $DOCKER_REGISTRY_USER --password-stdin localhost:5000'
                        }
                        script {
                            env.BUILD_VERSION = 'latest'
                            sh "docker build -t localhost:5000/${SERVICE_NAME}:${env.BUILD_VERSION} ."
                            sh "docker push localhost:5000/${SERVICE_NAME}:${env.BUILD_VERSION}"
                            sh "docker image rm localhost:5000/${SERVICE_NAME}:${env.BUILD_VERSION}"
                        }
                    }
                }
                stage("clone ansible") {
                    steps {
                        script {
                            dir('ansible') {
                                git branch: 'master',
                                credentialsId: 'symper_git',
                                url: 'https://github.com/devsymper/ci-cd-ansible-script.git'
                            }
                        }
                    }
                }
                stage("deploy to k8s") {
                    steps{
                        withCredentials([
                            usernamePassword(credentialsId: 'ssh_qc_vps', passwordVariable: 'USER_PASS', usernameVariable: 'USER_NAME'),
                        ]) {
                            ansiblePlaybook (
                                installation: 'Ansible',
                                inventory: 'ansible/inventories/staging/hosts',
                                playbook: 'ansible/playbooks/symper-k8s-deploy.yaml',
                                credentialsId: "ssh_qc_key",
                                vaultCredentialsId: "ansible_vault_file",
                                disableHostKeyChecking: true,
                                extraVars: [
                                    SERVICE_ENV: "$SERVICE_ENV",
                                    SERVICE_NAME: "$SERVICE_NAME",
                                    APP_NAME: "$APP_NAME",
                                    BUILD_VERSION: "$BUILD_VERSION",
                                    POSTGRES_DB: "$POSTGRES_DB",
                                    POSTGRES_HOST: "$POSTGRES_HOST",
                                    CACHE_HOST: "$CACHE_HOST",
                                    KAFKA_PREFIX: "$KAFKA_PREFIX",
                                    HOST_DOMAIN: "$HOST_DOMAIN",
                                    TARGET_HOST: "appservers"
                                ]
                            )
                        }
                    }
                }
                stage("triggerkafka") {
                    steps {
                        script {
                            if ("${KAFKA_SUBCRIBE}".toBoolean() == true) {
                                sh 'curl --connect-timeout 1 -s -I -X GET https://${SERVICE_ENV}-${SERVICE_NAME}/KafkaService/subscribe | grep HTTP/ | awk \'{print "Code: "  $2}\''
                            } else{
                                echo 'None Kafka subscriber'
                            }
                        }
                    }
                }
            }
        }

        stage ("production") {
            // when {
            //     allOf{
            //         expression {
            //             return AUTHOR_NAME == "devsymper"
            //         }
            //         expression{
            //             branch "tags/*"
            //         }
            //     }
            // }
            environment {
                SERVICE_ENV = "prod"
                POSTGRES_HOST = "10.20.166.193,10.20.166.235"
                SSH_HOST = "10.20.166.246"
                POSTGRES_DB = "accesscontrol_symper_vn"
                CLICKHOUSE_HOST = "10.20.166.166"
                CACHE_HOST= "redis-server.redis-server.svc.cluster.local"
                KAFKA_PREFIX = "10.20.166.6"
            }
            stages {
                stage("test") {
                    steps {
                        script {
                            sh 'echo $AUTHOR_NAME'
                            sh 'echo $BRANCH_NAME'
                        }
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
                // stage("checkin deployment state") {
                //     steps{
                //         withCredentials([
                //             usernamePassword(credentialsId: 'ssh_prod_vps', passwordVariable: 'USER_PASS', usernameVariable: 'USER_NAME')
                //         ]) {
                //             script {
                //                 sshagent(['prod_ssh_key']) {
                //                     try{
                //                         env.CURRENT_ROLE=sh (returnStdout: true, 
                //                                             script: "ssh -o StrictHostKeyChecking=no $USER_NAME@$SSH_HOST 'echo -e \'$USER_PASS\' | sudo -S kubectl get services --field-selector metadata.name=\"$APP_NAME\" -o jsonpath={.items[0].spec.selector.role}'").trim()
                //                     } catch (Exception e) {
                //                         echo "$e" }
                //                 }
                //                 sh "echo role ${env.CURRENT_ROLE}"
                //                 if("$env.CURRENT_ROLE" == "" || "$env.CURRENT_ROLE" == "green") {
                //                     env.CURRENT_ROLE = "green"
                //                     env.TARGET_ROLE = "blue"
                //                 } else {
                //                     env.TARGET_ROLE = "green"
                //                 }
                //             }
                //         }
                //     }
                // }
                // stage("deploy to k8s"){
                //     steps{
                //         withCredentials([
                //             usernamePassword(credentialsId: 'ssh_prod_vps', passwordVariable: 'USER_PASS', usernameVariable: 'USER_NAME'),
                //         ]) {
                //             sh "chmod +x shellscripts/*"
                //             sshagent(['prod_ssh_key']) {
                //                 sh '''
                //                     echo $TARGET_ROLE
                //                     echo $CURRENT_ROLE
                //                 '''
                //                 sh "./shellscripts/updateManifests.sh"
                //                 sh "./shellscripts/deployService.sh"
                //             }
                //         }
                //     }
                // }
                // stage("triggerkafka") {
                //     steps {
                //         script {
                //             if ("${KAFKA_SUBCRIBE}".toBoolean() == true) {
                //                 sh 'curl --connect-timeout 1 -s -I -X GET https://${SERVICE_NAME}/KafkaService/subscribe | grep HTTP/ | awk \'{print "Code: "  $2}\''
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
        always{
            echo "${env.SERVICE_NAME} - Build # ${env.BUILD_NUMBER} - ${env.BUILD_STATUS}: \nCheck console output at ${env.BUILD_URL} to view the results."
        }
        success{
            office365ConnectorSend webhookUrl: MSTEAMS_WEBHOOK,
                message: "${env.SERVICE_NAME} - Build # ${env.BUILD_NUMBER} - <span style=\"color:green\">${currentBuild.currentResult}</span>: <br> <strong style=\"color: deeppink;\">${env.COMMIT_MESSAGE}</strong>  <br> Check console output at ${env.BUILD_URL} to view the results.",
                status: 'Success'  
            echo "========pipeline executed successfully ========"
        }
        failure{
            office365ConnectorSend webhookUrl: MSTEAMS_WEBHOOK,
                message: "${env.SERVICE_NAME} - Build # ${env.BUILD_NUMBER} - <span style=\"color:red\">${currentBuild.currentResult}</span>: <br> <strong style=\"color: deeppink;\">${env.COMMIT_MESSAGE}</strong> <br> Check console output at ${env.BUILD_URL} to view the results.",
                status: 'Failed'   
            echo "========pipeline execution failed========"
        }
    }
}
